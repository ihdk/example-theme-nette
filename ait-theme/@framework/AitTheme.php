<?php


/**
 * Main framework static class for bootstraping theme
 */
final class AitTheme extends NObject
{
	/**
	 * Flag if it configuraiton loaded
	 * @var boolean
	 */
	private static $alreadyRan = false;

	/**
	 * Default Theme Configuration
	 * @var array
	 */
	protected static $configuration = array(
		'frontend-ajax'       => array(),
		'menus'               => array(),
		'theme-support'       => array(
			'html5'          => array('search-form', 'comment-form', 'comment-list'),
			'wplatte',
		),
		'ait-theme-support'   => array(),
		'sidebars'            => array(), // not used when they are configured via @theme.neon
		'page-post-metaboxes' => array(),
		'builtin-assets'      => array(),
		'assets'              => array(),
		'plugins'             => array(),
		'paid-plugins'             => array(),
		'editor-style'        => true,
		'widget-output-cache' => 0, // recommended 10min * 60s = 600
	);

	/**
	 * Object for manipulating with Neon configs
	 * @var AitConfig
	 */
	protected static $config;

	/**
	 * Object for manipulating with saved Ait Theme options in wp_options table
	 * @var AitOptions
	 */
	protected static $options;

	/**
	 * Managers
	 * @var array
	 */
	protected static $managers = array();

	/**
	 * Factories
	 * @var array
	 */
	protected static $factories = array();

	/**
	 * Metaboxes for Page type or Post type
	 * @var array
	 */
	protected static $pagePostMetaboxes = array();


	public function __construct()
	{
		throw new LogicException(__CLASS__ . ' is a static class. Can not be instantiate.');
	}



	public static function getConfiguration($key = '')
	{
		if($key){
			return self::$configuration[$key];
		}
		return self::$configuration;
	}



	public static function getConfig()
	{
		return self::$config;
	}



	public static function getOptions()
	{
		return self::$options;
	}



	public static function getManager($manager)
	{
		if(isset(self::$managers[$manager])){
			return self::$managers[$manager];
		}else{
			trigger_error(sprintf("Manager '{$manager}' does not exist. Available managers are: %s", implode(', ', array_keys(self::$managers))), E_USER_WARNING);
		}

		return false;
	}



	public static function getFactory($factory)
	{
		if(isset(self::$factories[$factory])){
			return self::$factories[$factory];
		}else{
			trigger_error(sprintf("Factory '{$factory}' does not exist. Available factories are: %s", implode(', ', array_keys(self::$factories))), E_USER_WARNING);
		}

		return false;
	}



	/**
	 * Runs the theme
	 */
	public static function run($configurationFilepath = '')
	{
		// if it was called in child theme do not call it again
		if(self::$alreadyRan) return;

		self::loadTextdomain();

		if(!is_array($configurationFilepath)){
			$configuration = include $configurationFilepath;
		}else{
			$configuration = $configurationFilepath;
		}

		$configuration = apply_filters('ait-theme-configuration', $configuration);

		self::prepareConfiguration($configuration);

		do_action('ait-theme-run');


		self::addThemeSupport();

		self::createFactories();

		self::$config = new AitConfig();
		self::$options = new AitOptions(self::$config, self::getFactory('options-controls-group')); // here are loaded configs

		self::createManagers();


		self::$options->setElementsPrototypes(self::getManager('elements')->getPrototypes());

		AitUpgrader::run();

		// Admin init
		if(is_admin())  AitAdmin::run();


		add_action('after_setup_theme', array(__CLASS__, 'onAfterSetupTheme'));

		self::$alreadyRan = true;

	}



	public static function prepareConfiguration($configuration)
	{
		$assets = array();

		if(!is_admin()){
			$assetsFilePath = aitPath('config', '/builtin-assets.php');

			if($assetsFilePath === false){
				$assets = require aitPaths()->dir->fwConfig . '/builtin-assets.php';
			}else{
				$assets = require $assetsFilePath;
			}
			self::$configuration['builtin-assets'] = apply_filters('ait-builtin-assets', (array) $assets);
		}

		self::$configuration = array_replace_recursive(self::$configuration, $configuration);

		// These are core elements, and have to be always supported
		self::$configuration['ait-theme-support']['elements'][] = 'content';
		self::$configuration['ait-theme-support']['elements'][] = 'comments';
		self::$configuration['ait-theme-support']['elements'][] = 'sidebars-boundary-start';
		self::$configuration['ait-theme-support']['elements'][] = 'sidebars-boundary-end';

		self::preparePluginsConfigration();
	}



	/**
	 * 'after_setup_theme' action callback
	 *   * Loads text domain
	 *   * Adds editor style
	 *   * Registers Sidebars
	 *   * Registers Widgets
	 *   * Registers Menus
	 *   * Adds theme support
	 *   * Adds custom metaboxes for Post or Page types
	 *   * Registers frontend ajax
	 *   * Calls additional custom after-setup-theme callbacks
	 */
	public static function onAfterSetupTheme()
	{
		if(self::$configuration['editor-style'] === true){
			add_editor_style('design/css/editor-style.css');
		}elseif(is_string(self::$configuration['editor-style'])){
			add_editor_style(trim(self::$configuration['editor-style'], '/'));
		}

		if(is_user_logged_in()) AitAdminBar::register();

		AitWoocommerce::init();

		AitWpOverrides::init();

		AitWpExtensions::register();

		self::getManager('sidebars')->registerSidebars();
		self::getManager('sidebars')->registerWidgets();

		self::registerMenus();

		if(current_theme_supports('ait-megamenu')){
			$megamenu = aitOptions()->get('theme')->megamenu;
			if($megamenu->enabled and class_exists('AitMenu')){
				AitMenu::init();
			}
		}else{
			add_filter('ait-get-full-config', function($config){
				unset($config['megamenu']);
				return $config;
			});
		}

		self::overrideCptsMetaboxesConfigs();

		self::tgmpa();

		self::addPageOrPostMetaboxes();

		if(AitUtils::isAjax()){
			self::registerFrontendAjax();
		}

		add_action('init', array(__CLASS__, 'onInit'));
	}



	/**
	 * 'init' action callback
	 *   * Registers CPTs
	 *   * Initialize WpLatte
	 */
	public static function onInit()
	{
		add_action('wp_enqueue_scripts', array(__CLASS__, 'onEnqueueScriptsAndStyles'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'onEnqueueAdminScriptsAndStyles'));
		add_action('wp_head', array(__CLASS__, 'wp_head'));

		$isAjax = (defined('DOING_AJAX') and DOING_AJAX === true);

		add_action('http_api_curl', function ($handle, $request, $url) {
			$domain = explode('/', preg_replace('|https?://|', '', $url))[0];
			if (strpos($domain, 'ait-themes.club') !== false && defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
				curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			}
		}, 10, 3);

		if (!$isAjax) {
			AitLicensing::handleConfig();
			if (!AitLicensing::isOk()) {
				AitLicensing::adminNotice();
			}
			if (AitLicensing::isUnauthorized()) {
				AitLicensing::frontendNotice();
			}
		}

		add_action('wp', array(__CLASS__, 'initWpLatte'), 1999);
	}

	public static function wp_head() {
		self::getManager('assets')->initGlobalFrontendJsVariables();
	}

	public static function initWpLatte()
	{
		if(!is_admin() and !AitUtils::isAjax()){
			AitWpLatte::init();
		}
	}



	/**
	 * 'wp_enqueue_scripts' action callback
	 *   * Enqueues built-in assets and custom theme css and js
	 */
	public static function onEnqueueScriptsAndStyles()
	{
		self::getManager('assets')->enqueueFrontendAssets();
	}



	/**
	 * 'wp_enqueue_scripts' action callback
	 *   * Enqueues built-in assets and custom theme css and js
	 */
	public static function onEnqueueAdminScriptsAndStyles()
	{
		self::getManager('assets')->enqueueAdminAssets();
	}



	public static function createFactories()
	{
		self::$factories['option-control'] = new AitOptionControlFactory();
		self::$factories['options-controls-group'] = new AitOptionsControlsGroupFactory(self::$factories['option-control']);
	}



	/**
	 * Creates managers
	 */
	public static function createManagers()
	{
		self::$managers['assets'] = new AitAssetsManager(
			self::$configuration['builtin-assets'],
			self::$configuration['assets']
		);

		if(aitIsPluginActive('toolkit')){
			self::$managers['cpts'] = AitToolkit::getManager('cpts');
		}

		self::$managers['elements'] = new AitElementsManager(
			self::$config->getFullConfig('elements'),
			self::$config->getDefaults('elements'),
			self::$managers['assets'],
			self::$factories['options-controls-group']
		);

		self::$managers['sidebars'] = new AitSidebarsManager(
			self::$configuration['sidebars'],
			self::$options->getOptionsByType('theme'),
			self::$configuration['widget-output-cache']
		);
	}



	/**
	 * Loads text domain and languages
	 */
	public static function loadTextdomain()
	{
		$isAdmin = false;
		if(defined('PLL_ADMIN') and PLL_ADMIN){
			$isAdmin = true;
		}elseif(is_admin()){
			$isAdmin = true;
		}

		$isFrontendAjax = false;
		if(defined('PLL_AJAX_ON_FRONT') and PLL_AJAX_ON_FRONT){
			$isFrontendAjax = true;
		}elseif(AitUtils::isAjax() and !AitUtils::contains(wp_get_referer(), '/wp-admin/')){
			$isFrontendAjax = true;
		}

		if(!$isAdmin or $isFrontendAjax){
			$maybeFilteredLocale = apply_filters('theme_locale', get_locale(), 'ait');
			if(!$maybeFilteredLocale){
				global $locale;
				$maybeFilteredLocale = $locale;
			}
			load_textdomain('ait', aitPath('languages', "/{$maybeFilteredLocale}.mo"));
		}


		if($isAdmin and !$isFrontendAjax){
			$maybeFilteredLocale = apply_filters('theme_locale', get_locale(), 'ait-admin');
			if(!$maybeFilteredLocale){
				global $locale;
				$maybeFilteredLocale = $locale;
			}
			load_textdomain('ait-admin', aitPath('languages', "/admin-{$maybeFilteredLocale}.mo"));
		}
	}



	/**
	 * Adds and registers menus
	 * @param  array $menus Same array as for register_nav_menus() fn
	 */
	public static function registerMenus()
	{
		// this condition prevents calling add_theme_support( 'menus' ) in register_nav_menus fn
		if(!empty(self::$configuration['menus']))
			register_nav_menus(self::$configuration['menus']);
	}



	/**
	 * Add theme features
	 * @param array $features All features that theme must support
	 */
	public static function addThemeSupport()
	{
		foreach(self::$configuration['theme-support'] as $name => $args){
			if(!is_string($name)){
				add_theme_support($args);
			}else{
				add_theme_support($name, $args);
			}
		}

		foreach(self::$configuration['ait-theme-support'] as $name => $args){
			if(is_int($name)){ // like: megamenu
				$feature = $args;
				$feature = AitUtils::addPrefix($feature, '', 'ait-');
				add_theme_support($feature);
			}else{ // like cpt => array(...)
				if(is_array($args)){
					if($name === 'elements' or $name === 'cpts'){
						$args = array_unique($args);
					}
					foreach($args as $key => $value){
						if($name === 'cpts'){
							add_theme_support("ait-cpt-{$value}");
						}elseif($name === 'elements'){
							add_theme_support("ait-element-{$value}");
						}else{
							$feature = AitUtils::addPrefix($name, '', 'ait-');
							add_theme_support($feature, $args);
							break;
						}
					}
				}
			}
		}
	}



	protected static function preparePluginsConfigration()
	{
		$builtinPluginList = require aitPaths()->dir->fwConfig . '/plugins.php';

		$plugins = apply_filters('ait-builtin-plugin-list', array_replace_recursive($builtinPluginList, self::$configuration['plugins']));

		$list = array('plugins' => array(), 'paid-plugins' => array());

		foreach($plugins as $slug => $plugin){
			if(isset($plugin['ait-packages']) and !in_array('basic', $plugin['ait-packages'])){
				$list['paid-plugins'][$slug] = $plugin;
				$list['paid-plugins'][$slug]['slug'] = $slug;
			}else{
				$list['plugins'][$slug] = $plugin;
				$list['plugins'][$slug]['slug'] = $slug;
			}

			if($slug === 'revslider'){
				$revsliderInstalled = file_exists(WP_PLUGIN_DIR . "/revslider/");
				$aitFileDoesNotExist = !file_exists(WP_PLUGIN_DIR . "/revslider/ait-revslider.php");
				// skip if it is not AIT version of RevSlider
				if($revsliderInstalled and $aitFileDoesNotExist) continue;
			}

			// back compatibility
			if(isset($plugin['source']) and AitUtils::startsWith($plugin['source'], '/plugins/')){
				$list['paid-plugins'][$slug]['source'] = aitPaths()->dir->ait . $plugin['source'];
				$list['plugins'][$slug]['source'] = aitPaths()->dir->ait . $plugin['source'];
			}

			// add AIT plugins to theme support
			add_theme_support($slug . '-plugin');
		}

		self::$configuration['plugins'] = $list['plugins'];
		self::$configuration['paid-plugins'] = $list['paid-plugins'];

		add_theme_support('ait-languages-plugin');
	}



	/**
	 * Registers TGMPA action
	 */
	protected static function tgmpa()
	{
		require_once aitPaths()->dir->libs . '/class-tgm-plugin-activation.php';

		if(is_admin() and !AitUtils::isAjax()){
			add_action('tgmpa_register', array(__CLASS__, 'tgmpaRegisterPlugins'));
		}
	}



	/**
	 * Registers and starts TGMPA
	 */
	public static function tgmpaRegisterPlugins()
	{
		$config = array(
			'id'               => 'ait-tgmpa',
			'parent_slug'      => 'plugins.php',
			'menu'             => 'install-required-plugins',
			'is_automatic'     => true,
			'strings'          => array(
				'menu_title'   => __('Install Theme Plugins', 'ait-admin'),
				'page_title'   => __('Install Plugins Recommended / Required by Theme', 'ait-admin'),
			),
		);

		if(!empty(self::$configuration['plugins']) or !empty(self::$configuration['paid-plugins'])){
			$plugins = self::$configuration['plugins'];
			if(!empty(self::$configuration['paid-plugins']) and AIT_THEME_PACKAGE !== 'basic'){
				$plugins = array_merge($plugins, self::$configuration['paid-plugins']);
			}
			tgmpa($plugins, $config);
		}
	}



	/**
	 * Batch adding Post and Page metaboxes
	 */
	public static function addPageOrPostMetaboxes()
	{
		foreach(self::$configuration['page-post-metaboxes'] as $id => $params){
			self::addPageOrPostMetabox($id, $params);
		}
	}



	/**
	 * Adds custom metaboxes for post, page and user
	 * @param array $metaboxes
	 */
	public static function addPageOrPostMetabox($id, $params)
	{
		if(isset($params['id'])) unset($params['id']);

		if(isset($params['types'])){
			$params['types'] = array_filter($params['types'], function($type) {
				return ($type == 'page' or $type == 'post' or $type == 'user');
			}); // filter out all custom types except post, page, 'user'
		}

		if(isset($params['config'])){
			if(!AitUtils::endsWith($params['config'], 'metabox.neon')){
				$config = aitPath('config', "/metaboxes/{$params['config']}.metabox.neon");
				$params['config'] = $config;
			}
		}

		if (in_array('user', $params['types'])) {
			$defaults = array(
				'metaKey' => "_user_$id",
			);
		} else {
			$defaults = array(
				'metaKey' => "_post_$id",
			);
		}

		$internalId = "ait-$id";

		self::$pagePostMetaboxes[$id] = new AitMetaBox($id, $internalId, array_merge($defaults, $params));
	}



	/**
	 * Registers frontend ajax callbacks
	 */
	public static function registerFrontendAjax()
	{
		$ajaxActions = array();

		foreach(self::$configuration['frontend-ajax'] as $classId){
			$class = AitUtils::id2class($classId, 'Ajax');

			$instance = new $class();

			$methods = get_class_methods($class);
			$r = new NClassReflection($class); // little overkill, checking 'WpAjax' string in phpDoc comment would be enough


			foreach($methods as $method){
				if($r->getMethod($method)->getAnnotation('WpAjax') === true){
					$ajaxActions["{$classId}:{$method}"] = "{$classId}:{$method}";
					add_action("wp_ajax_{$classId}:{$method}", array($instance, $method)); // Authenticated actions
					add_action("wp_ajax_nopriv_{$classId}:{$method}", array($instance, $method)); // Non-admin actions
				}
			}
		}

		self::getManager('assets')->setAjaxActions($ajaxActions);
	}



	private static function overrideCptsMetaboxesConfigs()
	{
		add_filter('ait-cpt-metabox-config-path', array(__CLASS__, 'overrideCptMetaboxConfig'), 10, 2);
	}



	public static function overrideCptMetaboxConfig($path, $filename)
	{
		$paths = aitPaths();
		$newPath = '';
		if(AitUtils::endsWith($filename, '.php')){
			$basename = basename($filename, '.php');
			$newPath = aitPath('config', "/cpts-metaboxes/{$basename}.php");
			if(!$newPath){
				$newPath = aitPath('config', "/cpts-metaboxes/{$basename}.neon");
			}
		}else{
			$newPath = aitPath('config', "/cpts-metaboxes/{$filename}");
		}

		return $newPath ? $newPath : $path;
	}
}
