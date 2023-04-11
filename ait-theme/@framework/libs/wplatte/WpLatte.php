<?php


define('WPLATTE_VERSION', '2.9.2');


/**
 * Main class which connects Nette Latte and WordPress
 */
class WpLatte
{

	/**
	 * Configuration of WpLatte
	 * @var array
	 */
	public static $config = array();

	/**
	 * Default / global variables for WpLatte templates
	 * @var array
	 */
	public static $params = array();

	/**
	 * Translations object with translations for given domain
	 * @var object
	 */
	protected static $translationsForDomain;

	/**
	 * get_* Templates included by get_header(), get_footer(), get_sidebar(), comments_template()
	 * @var array
	 */
	protected static $templatesStorage = array();

	/**
	 * Stores names of entities classes, this way can be altered via filter
	 * @var array
	 */
	protected static $entitiesClasses = array(
		'Archive'       => 'WpLatteArchiveEntity',
		'Attachment'    => 'WpLatteAttachmentEntity',
		'Category'      => 'WpLatteCategoryEntity',
		'Comment'       => 'WpLatteCommentEntity',
		'CommentAuthor' => 'WpLatteCommentAuthorEntity',
		'Post'          => 'WpLattePostEntity',
		'PostAuthor'    => 'WpLattePostAuthorEntity',
		'Tag'           => 'WpLatteTagEntity',
		'TaxonomyTerm'  => 'WpLatteTaxonomyTermEntity',
		'Wp'            => 'WpLatteWpEntity',
	);



	/**
	 * Initialisation of WpLatte
	 * This function should be called in 'after_setup_theme' hook function
	 *
	 * @param  array  $args Parameter for configuring WpLatte and passing default template parameters
	 * @return void
	 */
	public static function init($args = array())
	{
		// defaults
 		self::$config= array(
			'themeDir' => get_template_directory(),
			'childThemeDir' => get_stylesheet_directory(),
			'langDomain' => 'wplatte',
		);

		if(isset($args['config']) and !empty($args['config'])){
			self::$config = array_merge(self::$config, $args['config']);
		}

		if(isset($args['params']) and !empty($args['params'])){
			self::$params = array_merge(self::$params, $args['params']);
		}

		if(self::$config['langDomain'] != 'wplatte'){
			self::$translationsForDomain = get_translations_for_domain(self::$config['langDomain']);
		}

		self::$entitiesClasses = apply_filters('wplatte-entities-classes', self::$entitiesClasses);

		WpLatteTemplateHierarchy::register();

		add_filter('template_include', __CLASS__ . '::templateIncludeCallback', 1999);
		add_filter('woocommerce_before_template_part', __CLASS__ . '::woocommerceTemplateIncludeCallback', 1999, 4);


		add_action('get_header', array(__CLASS__, 'headerTemplateCallback'));
		add_action('get_footer', array(__CLASS__, 'footerTemplateCallback'));
		add_action('get_sidebar', array(__CLASS__, 'sidebarTemplateCallback'));
		add_filter('comments_template', array(__CLASS__, 'commentsTemplateCallback'), 1999);
		add_filter('get_search_form', array(__CLASS__, 'searchFormTemplateCallback'));

		if(self::$config['langDomain'] != 'wplatte'){
			add_filter('gettext', array(__CLASS__, 'gettextCallback'), 10, 3);
			add_filter('gettext_with_context', array(__CLASS__, 'gettextWithContextCallback'), 10, 4);
			add_filter('ngettext', array(__CLASS__, 'ngettextCallback'), 10, 5);
			add_filter('ngettext_with_context', array(__CLASS__, 'ngettextWithContextCallback'), 10, 6);
		}

		global $pagenow;

		if(isset($pagenow) and $pagenow === 'wp-signup.php'){
			self::fixSignupPage();
		}
	}



	/**
	 * Callback for 'template_include' filter
	 * @param  string $template Absolute path to template file
	 * @return string           Absolute path to template file
	 */
	public static function templateIncludeCallback($template)
	{
		self::render($template);
	}



	public static function woocommerceTemplateIncludeCallback($templateName, $templatePath, $located, $args)
	{
		if($templateName == 'archive-product.php'){
			self::render($located, $args);
			exit;
		}
	}



	/**
	 * Renders Latte templates
	 * @param  string $template Patho to template
	 * @return void
	 */
	public static function render($template, $params = array())
	{
		self::$params['homeUrl'] = self::homeUrl();
		self::$params['searchUrl'] = self::homeUrl(true);

		self::$params = array_merge(self::$params, $params);
		$layoutParams = apply_filters('wplatte-layout-params', self::$params);

		ob_start();
		self::createTemplate($template, $layoutParams)->render();
		$content = ob_get_clean();

		// Workaround... Why can not get_[header, footer, sidebar] functions have filters
		// to filter html output instead of quite useless actions?
		$content = self::removeRawIncludedContent(did_action('get_header'), $content, 'header');
		$content = self::removeRawIncludedContent(did_action('get_footer'), $content, 'footer');
		$content = self::removeRawIncludedContent(did_action('get_sidebar'), $content, 'sidebar');

		do_action('wplatte-before-output', $content);
		echo $content;
		do_action('wplatte-after-output', $content);
	}



	public static function headerTemplateCallback($name)
	{
		remove_action('wp_head', 'wpmu_activate_stylesheet');
		self::renderHeaderFooterSidebarTemplate('header', $name);
	}



	public static function footerTemplateCallback($name)
	{
		self::renderHeaderFooterSidebarTemplate('footer', $name);
	}



	public static function sidebarTemplateCallback($name)
	{
		self::renderHeaderFooterSidebarTemplate('sidebar', $name);
	}



	public static function commentsTemplateCallback($commentsTemplate)
	{
		global $post;

		if(!NStrings::contains($commentsTemplate, self::$config['childThemeDir']))
			return $commentsTemplate;

		$layoutParams = apply_filters('wplatte-layout-params', self::$params);

		$templates = array();

		// Allow for custom templates entered into comments_template( $file ).
		$template = str_replace(trailingslashit(self::$config['childThemeDir']), '', $commentsTemplate);

		if($template !== 'comments.php')
			$templates[] = $template;

		 // Add a comments template based on the post type.
		$templates[] = 'comments-' . get_post_type() . '.php';
		$templates[] = 'comments-' . WpLatteUtils::stripPrefix('post', get_post_type()) . '.php';

		$templates[] = 'comments.php';

		$template = locate_template($templates);

		// comments_template filter expects path to comments template, otherwise will output parent theme's comments.php; solution is returning blank page
		//$blank = dirname(__FILE__) . '/blank-comments-template.php';
		$blank = get_template_directory() . '/ait-theme/@framework/libs/wplatte/blank-comments-template.php';

		if(post_password_required()) return $blank;

		self::createTemplate($template, array('post' => self::createEntity('Post', $post)) + $layoutParams)->render();

		return $blank;
    }



	public static function searchFormTemplateCallback($form)
	{
		$layoutParams = apply_filters('wplatte-layout-params', self::$params);

		$templates = array(
			'parts/search-form.php', // ait flavour
			'search-form.php',
			'searchform.php',
		);

		$template = locate_template($templates);

		$return = (string) self::createTemplate($template, $layoutParams)->render();

		return $return;
	}



	public static function renderHeaderFooterSidebarTemplate($template, $name)
	{
		$layoutParams = apply_filters('wplatte-layout-params', self::$params);
		$templates = array();

		if(!empty($name)){
			$templates[] = "{$template}-{$name}.latte";
			$templates[] = "{$template}-{$name}.php";
		}

		$templates[] = "$template.latte";
		$templates[] = "$template.php";

		self::$templatesStorage[$template][$name] = locate_template($templates);

		self::createTemplate(self::$templatesStorage[$template][$name], $layoutParams)->render();
	}



	/**
	 * Workaround for get_* template functions. TODO: Needs better solution.
	 * After execution action, e.g. do_action('get_footer'), functions continues
	 * and includes raw content of the WpLatte template.
	 */
	public static function removeRawIncludedContent($condition, $content, $template)
	{
		if($condition){
			foreach((array) self::$templatesStorage[$template] as $i => $file){
				$f = file_get_contents($file);
				$content = str_replace($f, '', $content);
			}
			return $content;
		}
		return $content;
	}



	/**
	 * Automaticaly creates some special entities
	 * according where are we on site
	 */
	public static function createTemplateEntities()
	{
		$entities = array();

		$entities['blog'] = null;

		if(is_tax()){
			$entities['taxonomyTerm'] = self::createEntity('TaxonomyTerm', get_queried_object());

		}elseif(is_category()){
			$entities['category'] = self::createEntity('Category', get_queried_object());

		}elseif(is_tag()){
			$entities['tag'] = self::createEntity('Tag', get_queried_object());

		}elseif(is_author()){
			$entities['author'] = self::createEntity('PostAuthor', get_queried_object());

		}elseif(is_archive()){
			$entities['archive'] = self::createEntity('Archive');

		}elseif(is_singular()){
			$entities['post'] = self::createEntity('Post', get_queried_object());

		}elseif(is_home()){
			$obj = get_queried_object();
			if(isset($obj->post_status) or $obj instanceof WP_Post){ // check if queried object is $post
				$entities['blog'] = $obj ? self::createEntity('Post', $obj) : null;
			}
		}

		return $entities;
	}



	/**
	 * WpLatte Template factory
	 * Creates new Latte template object
	 *
	 * @param array $params   Params for template
	 * @param string          Absolute path to custom template
	 * @return NFileTemplate  Instance of NFileTemplate
	 */
	public static function createTemplate($template, $params = array())
	{
		$name = basename($template, '.php');

		$entities = self::createTemplateEntities();
		$params = array_merge($params, $entities);

		$params = apply_filters("wplatte-template-{$name}-params", self::$params + $params, $template);

		$params['wp'] = self::createEntity('Wp');

        $params['currentTemplate'] = $template;
        $params['currentTemplateName'] = $name;

		$wplattetemplate = self::setupTemplating();

		$wplattetemplate->setFile($template);
		$wplattetemplate->setParameters($params);

		return $wplattetemplate;
	}



	/**
	 * Prepares Nette template with registered Latte filter and template helpers
	 * @param  string $file   Absolute path to template file
	 * @param  array $params  Parameters for template
	 * @return NFileTemplate  Prepared template object
	 */
	protected static function setupTemplating()
	{
		if($result = WpLatteObjectCache::load('setup-templating'))
			return $result;

		$template = new WpLatteFileTemplate();

		$fakePresenter = new WpLatteFakePresenter;
		$fakePresenter->paths = array(
			'child' => self::$config['childThemeDir'],
			'theme' => self::$config['themeDir'],
		);

		$template->_control = $fakePresenter;

		$template->registerHelperLoader('WpLatteTemplateHelpers::loader');
		$template->registerHelperLoader('NTemplateHelpers::loader');

		$template->setCacheStorage(new NPhpFileStorage(realpath(self::$config['cacheDir'])));

		$template->onPrepareFilters[] = callback('WpLatte::createLatteFilterCallback');

		WpLatteObjectCache::save('setup-templating', $template);

		return $template;
	}



	/**
	 * Registers Latte filter and macros - default and wp extended macros
	 * @param  NFileTemplate $template Template object
	 * @return void
	 */
	public static function createLatteFilterCallback($template)
	{
		$latte = new NLatteFilter;
		WpLatteMacros::install($latte->compiler, self::$config);
		$template->registerFilter($latte);
	}



	/**
	 * Entities factory
	 * @param  string $entity Name of entity
	 * @param  mixed  $params Params for entity
	 * @return mixed          WpLatte Entity object
	 */
	public static function createEntity($entity, $params = null)
	{
		if(isset(self::$entitiesClasses[$entity])){
			$class = self::$entitiesClasses[$entity];
		}else{
			trigger_error("There isn't specified class for entity '{$entity}'", E_USER_ERROR); // fatal error
		}

		$params = func_get_args();
		array_shift($params);

		switch($entity){
			case 'Archive':
				return new $class();

			case 'Comment':
			case 'CommentAuthor':
				return new $class($params[0], $params[1]);

			case 'Attachment':
				return new $class($params[0], $params[1], $params[2]);

			case 'Wp':
				return call_user_func(array($class, 'getInstance'));

			case 'Post':
				$obj = $params[0];
				if($obj->post_type != 'attachment'){
					$entity = WpLatteUtils::camelize($obj->post_type);

					if(isset(self::$entitiesClasses[$entity])){
						$class = self::$entitiesClasses[$entity];  // e.g. Portfolio => JetpackPortfolioEntity
					}
				}
				return new $class($obj);
			default:
				return new $class($params[0]);
		}
	}



	public static function getEntityClass($entity)
	{
		if(isset(self::$entitiesClasses[$entity])){
			$class = self::$entitiesClasses[$entity];
			return self::$entitiesClasses[$entity];
		}else{
			trigger_error("There isn't such entity class '{$entity}'");
			return false;
		}
	}



	public static function gettextCallback($translated, $text, $domain)
	{
		if($domain == 'wplatte')
			$translated = self::$translationsForDomain->translate($text);

		return $translated;
	}



	public static function gettextWithContextCallback($translated, $text, $context, $domain)
	{
		if($domain == 'wplatte')
			$translated = self::$translationsForDomain->translate($text, $context);

		return $translated;
	}



	public static function ngettextCallback($translated, $single, $plural, $number, $domain)
	{
		if($domain == 'wplatte'){
			$translated = self::$translationsForDomain->translate_plural($single, $plural, $number);
		}

		return $translated;
	}



	public static function ngettextWithContextCallback($translated, $single, $plural, $number, $context, $domain)
	{
		if($domain == 'wplatte'){
			$translated = self::$translationsForDomain->translate_plural($single, $plural, $number, $context);
		}

		return $translated;
	}



	protected static function fixSignupPage()
	{
		add_action('wp', function(){ ob_start(); }, 2000);
		add_action('before_signup_form', array(__CLASS__, 'fixHeaderOnSignupPage') , 0);

		add_action('after_signup_form', function(){ ob_start(); }, 0);
		add_action('shutdown', array(__CLASS__, 'fixFooterOnSignupPage') , 0);
	}



	public static function fixHeaderOnSignupPage()
	{
		$headerHtml = ob_get_clean();

		echo self::removeRawIncludedContent(did_action('get_header'), $headerHtml, 'header');

		do_action('wplatte-after-signup-header');
	}



	public static function fixFooterOnSignupPage()
	{
		$footerHtml = ob_get_clean();

		do_action('wplatte-before-signup-footer');

		echo self::removeRawIncludedContent(did_action('get_footer'), $footerHtml, 'footer');
	}



	public static function homeUrl($isSearch = false)
	{
		global $polylang;

		if(function_exists('PLL')){
			return !empty(PLL()->links) ? PLL()->links->get_home_url(AitLangs::getCurrentLang(), $isSearch) : home_url('/');
		}

		if(isset($polylang)){
			return !empty($polylang->links) ? $polylang->links->get_home_url(AitLangs::getCurrentLang(), $isSearch) : home_url('/');
		}

		return home_url('/');
	}
}
