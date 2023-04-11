<?php


class AitWpLatte
{

	protected static $storage;



	/**
	 * Initialisation of WpLatte
	 */
	public static function init()
	{
		$minified = __DIR__ . '/libs/wplatte/wplatte.min.php';
		if(!class_exists('WpLatte') and file_exists($minified)){
			require $minified;
		}

		add_filter('wp_title', array(__CLASS__, 'wpTitle'), 10, 2);
		add_filter('body_class', array(__CLASS__, 'bodyHtmlClass'), 10, 2);

		add_action('wplatte-macros', 'AitWpLatteMacros::install', 10, 2);

		add_filter('wplatte-cache-constants', array(__CLASS__, 'cacheConstants'));
		add_filter('wplatte-layout-params', array(__CLASS__, 'layoutParams'));
		add_filter('wplatte-lang-domain', array(__CLASS__, 'langDomain'));
		add_filter('wplatte-cpt-prefixes', array(__CLASS__, 'addCptPrefixes'));
		add_filter('wplatte-post-meta', array(__CLASS__, 'postMeta'), 10, 6);
		add_filter('wplatte-menu-args', array(__CLASS__, 'menuArgs'), 10, 2);
		add_filter('wplatte-cpts', array(__CLASS__, 'addAitCpts'));
		add_filter('wplatte-taxs', array(__CLASS__, 'addAitTaxs'));
		add_filter('wplatte-custom-wpquery-args', array(__CLASS__, 'addLangToCustomWpQuery'), 10, 2);


		WpLatte::init(array(
			'config' => array(
				'cacheDir' => aitPaths()->dir->cache,
				'langDomain' => 'ait',
			),
		));

		WpLatteWpEntity::extensionMethod('WpLatteWpEntity::hasSidebar', array(__CLASS__, 'hasSidebar'));
		WpLatteWpEntity::extensionMethod('WpLatteWpEntity::sidebar', array(__CLASS__, 'getSidebar'));
		WpLatteWpEntity::extensionMethod('WpLatteWpEntity::widgetAreas', array(__CLASS__, 'getWidgetAreas'));
		WpLatteWpEntity::extensionMethod('WpLatteWpEntity::isWoocommerce', array(__CLASS__, 'isWoocommerce'));
	}



	/**
	 * Constants for invaliding WpLatte cache
	 * @param  array $constants
	 * @hookedTo WpLatteCacheConstants
	 * @return array
	 */
	public static function cacheConstants($constants)
	{
		$constants[] = 'AIT_THEME_VERSION';

		return $constants;
	}



	/**
	 * Additional params for layout
	 * @param  array $params
	 * @hookedTo WpLatteLayoutParams
	 * @return array
	 */
	public static function layoutParams($params)
	{
		$params['options'] = self::getCustomOptions();
		$params['elements'] = self::getCustomElementsOptions();
		$params['languages'] = AitLangs::getSwitcherLanguages();
		$params['currentLang'] = AitLangs::getCurrentLang();

		global $pagenow;
		$params['pagenow'] = $pagenow;

		return $params;
	}



	/**
	 * wp_title filter for wp_title() function
	 * @hookedTo wp_title
	 */
	public static function 	wpTitle($title, $sep)
	{
		global $paged, $page;

		if(is_feed())
			return $title;

		// Add the blog name.
		$title .= get_bloginfo('name');

		// Add the blog description for the home/front page.
		$site_description = get_bloginfo('description', 'display');

		if($site_description && (is_home() || is_front_page()))
			$title = "$title $sep $site_description";

		// Add a page number if necessary.
		if($paged >= 2 || $page >= 2)
			$title = "$title $sep " . sprintf(__('Page %s', 'ait'), max($paged, $page));

		return $title;
	}



	public static function bodyHtmlClass($classes, $class)
	{
		$elements = self::getCustomElementsOptions();
		$es = $elements->sortable;
		$eu = $elements->unsortable;

		foreach($es as $i){
			if($i->display and !$i->disabled and !in_array($i, array('sidebars-boundary-start', 'sidebars-boundary-end'))) $classes[] = "element-{$i->id}";
		}

		foreach($eu as $i){
			if($i->display and !$i->disabled) $classes[] = "element-{$i->id}";
		}


		foreach(self::getCurrentSidebars() as $side => $sidebar){
			if($sidebar['sidebar'] != 'none' and is_active_sidebar($sidebar['sidebar'])){
				$classes[] = "{$side}-sidebar";
			}
		}

		$options = self::getCustomOptions();

		if(isset($options->theme->general->layoutType))
			$classes[] = $options->theme->general->layoutType;

		if(isset($options->theme->general->progressivePageLoading) and $options->theme->general->progressivePageLoading)
			$classes[] = 'preloading-enabled';

		if(isset($options->theme->header->stickyMenu) and $options->theme->header->stickyMenu)
			$classes[] = 'sticky-menu-enabled';

		if(isset($options->theme->header->headerType))
			$classes[] = $options->theme->header->headerType;

		$classes = array_unique($classes);

		return $classes;
	}



	/**
	 * Merge global options and local options for queried page
	 * @param  string $key Part of the option key
	 * @return array      Merged options
	 */
	public static function getCustomOptions()
	{
		if(isset(self::$storage['custom-options'])){
			return self::$storage['custom-options'];
		}else{
			$oid = aitOptions()->getOid();
			$localOptions = aitOptions()->getOptions($oid);

			unset(
				$localOptions['elements'],
				$localOptions['theme']['adminBranding'],
				$localOptions['theme']['administrator']
			);

			$localOptions = apply_filters('ait-templates-options', $localOptions);
			$localOptions = self::filterOptionsForCurrentLocale($localOptions);

			$options = json_decode(json_encode($localOptions));

			self::$storage['custom-options'] = $options;

			return self::$storage['custom-options'];
		}
	}



	/**
	 * Merge global options and local options for queried page
	 * @param  string $key Part of the option key
	 * @return array      Merged options
	 */
	public static function getCustomElementsOptions()
	{
		if(isset(self::$storage['custom-elements-options'])){
			return self::$storage['custom-elements-options'];
		}else{
			$oid = aitOptions()->getOid();
			$o = aitOptions()->getOptions($oid);
			$options = $o['elements'];
			$em = aitManager('elements'); // shortcut

			$options = self::filterOptionsForCurrentLocale($options, 'elements');

			$elements = $em->createElementsFromOptions($options, $oid, true);

			$return = new stdClass;
			$return->unsortable = array();
			$return->sortable = array();

			$isBetweenSidebars = false;

			foreach($elements as $i => $el){
				if(!$em->getPrototype($el->getId()) and !$em->isElementSidebarsBoundary($el->getId())) continue; // in DB can be saved element, which is not available in theme

				if($el->isDisabled() and AIT_THEME_PACKAGE !== 'basic') continue;

				if($em->isElementSidebarsBoundary($el->getId()) and (!self::hasSidebar(__CLASS__, 'left') && !self::hasSidebar(__CLASS__, 'right'))) continue; // skip sidebars boundary elements if there are no sidebars

				if($el->getId() === 'comments' and aitOptions()->isQueryForSpecialPage(array('_404', '_search', '_archive', '_wc_product', '_wc_shop'))) continue; // skip comments element on some special pages

				if($el->sortable and !aitOptions()->isQueryForSpecialPage() and post_password_required() and $el->getId() === 'content'){
					// we need only Content Element
					$return->sortable = array();
					$return->sortable[$el->getId()] = $el;
					break;
				}

				if(!$el->sortable){
					$return->unsortable[$el->getId()] = $el;

				}elseif(isset($options[$i][$el->getId()]['@columns-element-index']) and $options[$i][$el->getId()]['@columns-element-index'] != '' and isset($return->sortable[$options[$i][$el->getId()]['@columns-element-index']])){
					$columnsElement = $return->sortable[$options[$i][$el->getId()]['@columns-element-index']];
					$columnsElement->addElementToColumn($el, $options[$i][$el->getId()]['@columns-element-column-index']);
				}else{
					if($em->isElementSidebarsBoundary($el->getId()) and $el->getId() == 'sidebars-boundary-start'){
						$isBetweenSidebars = true;
					}elseif($em->isElementSidebarsBoundary($el->getId()) and $el->getId() == 'sidebars-boundary-end'){
						$isBetweenSidebars = false;
					}

					$el->setBetweenSidebars($isBetweenSidebars);

					$return->sortable[$i] = $el;
				}
			}

			self::$storage['custom-elements-options'] = $return;

			return self::$storage['custom-elements-options'];
		}
	}



	protected static function filterOptionsForCurrentLocale($options, $type = '')
	{
		$translatablesList = aitConfig()->getTranslatablesList();
		$currentLocale = AitLangs::getCurrentLocale();
		$defaultLocale = AitLangs::getDefaultLocale();

		// theme options
		if($type != 'elements'){
			foreach($options as $configType => $groups){
				foreach($groups as $groupKey => $groupValues){
					foreach($groupValues as $optionKey => $optionValue){
						if(isset($translatablesList[$configType][$groupKey][$optionKey]) and $translatablesList[$configType][$groupKey][$optionKey]){
							if(is_array($optionValue) and isset($optionValue[$currentLocale])){

								$options[$configType][$groupKey][$optionKey] = apply_filters('ait-filter-value-for-current-locale', $optionValue[$currentLocale]);

							}elseif(is_array($optionValue) and is_int(key($optionValue))){ // clones
								foreach($optionValue as $i => $clones){
									foreach($clones as $k => $v){
										if(is_array($v) and isset($v[$currentLocale])){
											$options[$configType][$groupKey][$optionKey][$i][$k] = apply_filters('ait-filter-value-for-current-locale', $v[$currentLocale]);
										}elseif(is_array($v) and !isset($v[$currentLocale])){
											if(is_string($v)){
												$options[$configType][$groupKey][$optionKey][$i][$k] = apply_filters('ait-filter-value-for-current-locale', $v);
											}else{
												$val = '';
												if(isset($v[$defaultLocale])){
													$val = $v[$defaultLocale];
												}elseif(isset($v['en_US'])){
													$val = $v['en_US'];
												}
												$options[$configType][$groupKey][$optionKey][$i][$k] = apply_filters('ait-filter-value-for-current-locale', $val);
											}
										}
									}
								}
							}else{
								if(is_string($optionValue)){
									$options[$configType][$groupKey][$optionKey] = apply_filters('ait-filter-value-for-current-locale', $optionValue);
								}else{
									$val = '';
									if(isset($optionValue[$defaultLocale])){
										$val = $optionValue[$defaultLocale];
									}elseif(isset($optionValue['en_US'])){
										$val = $optionValue['en_US'];
									}
									$options[$configType][$groupKey][$optionKey] = apply_filters('ait-filter-value-for-current-locale', $val);
								}
							}
						}
					}
				}
			}
		// page builder
		}else{
			foreach($options as $i => $element){
				foreach($element as $elementId => $groupValues){
					foreach($groupValues as $optionKey => $optionValue){
						if(isset($translatablesList['elements'][$elementId][$optionKey]) and $translatablesList['elements'][$elementId][$optionKey]){
							if(is_array($optionValue) and isset($optionValue[$currentLocale])){

								$options[$i][$elementId][$optionKey] = apply_filters('ait-filter-value-for-current-locale', $optionValue[$currentLocale]);

							}elseif(is_array($optionValue) and is_numeric(key($optionValue))){ // clones
								foreach($optionValue as $j => $clones){
									foreach($clones as $k => $v){
										if(is_array($v) and isset($v[$currentLocale])){

											$options[$i][$elementId][$optionKey][$j][$k] = apply_filters('ait-filter-value-for-current-locale', $v[$currentLocale]);

										}elseif(is_array($v) and !isset($v[$currentLocale])){
											if(is_string($v)){
												$options[$i][$elementId][$optionKey][$j][$k] = apply_filters('ait-filter-value-for-current-locale', $v);
											}else{
												$val = '';
												if(isset($v[$defaultLocale])){
													$val = $v[$defaultLocale];
												}elseif(isset($v['en_US'])){
													$val = $v['en_US'];
												}
												$options[$i][$elementId][$optionKey][$j][$k] = apply_filters('ait-filter-value-for-current-locale', $val);
											}
										}
									}
								}
							}else{
								if(is_string($optionValue)){
									$options[$i][$elementId][$optionKey] = apply_filters('ait-filter-value-for-current-locale', $optionValue);
								}else{
									$val = '';
									if(isset($optionValue[$defaultLocale])){
										$val = $optionValue[$defaultLocale];
									}elseif(isset($optionValue['en_US'])){
										$val = $optionValue['en_US'];
									}
									$options[$i][$elementId][$optionKey] = apply_filters('ait-filter-value-for-current-locale', $val);
								}
							}
						}
					}
				}
			}
		}

		return $options;
	}



	protected static function getCurrentSidebars()
	{
		$opts = aitOptions()->getOptionsByType('layout');
		return isset($opts['@sidebars']) ? $opts['@sidebars'] : array();
	}



	/**
	 * Extension method for Wp Entity
	 * @param  WpLatteWpEntity  $wp       Current instance of $wp entity
	 * @param  string           $side     Which sidebar, e.g. right or left...
	 * @return bool
	 */
	public static function hasSidebar($wp, $side)
	{
		$currentSidebars = self::getCurrentSidebars();

		$registeredSidebars = $GLOBALS['wp_registered_sidebars'];

		return (
			isset($currentSidebars[$side]) and
			isset($currentSidebars[$side]['sidebar']) and $currentSidebars[$side]['sidebar'] != 'none' and
			isset($registeredSidebars[$currentSidebars[$side]['sidebar']])
		);
	}



	/**
	 * Extension method for Wp Entity
	 * @param  WpLatteWpEntity  $wp       Current instance of $wp entity
	 * @param  string           $side     Which sidebar, e.g. right or left...
	 * @return string                     Current selected sidebar
	 */
	public static function getSidebar($wp, $side)
	{
		$currentSidebars = self::getCurrentSidebars();

		if(isset($currentSidebars[$side]))
			return $currentSidebars[$side]['sidebar'];
		else
			return '';
	}



	/**
	 * Extension method for Wp Entity
	 * @param  WpLatteWpEntity  $wp       Current instance of $wp entity
	 * @param  string           $group    Which sidebar, e.g. right or left...
	 * @return string                     Current selected sidebar
	 */
	public static function getWidgetAreas($wp, $group)
	{
		$return = array();
		$areas = aitManager('sidebars')->getWidgetAreas();

		if(isset($areas[$group])){
			return array_keys($areas[$group]);
		}else{
			trigger_error("There isn't such widget areas group as '{$group}'");
			return array();
		}
	}



	/**
	 * Extension method for Wp Entity
	 * @param  WpLatteWpEntity  $wp       Current instance of $wp entity
	 * @param  string           $page    Type of woocommerce page: woocommerce, shop, shop, product, cart
	 * @return string                     Current selected sidebar
	 */
	public static function isWoocommerce($wp, $page = '')
	{
		if(!$page)
			return AitWoocommerce::currentPageIs('woocommerce');
		else
			return AitWoocommerce::currentPageIs($page);
	}



	/**
	 * Adds prefixes for CPT and custom taxonomy
	 * @param array  Default prefixes are empty strings
	 */
	public static function addCptPrefixes($prefixes)
	{
		return array(
			'post' => 'ait-',
			'taxonomy' => 'ait-',
		);
	}



	public static function postMeta($postmeta, $metaboxId, $metaboxKey, $key, $isCpt, $type)
	{
		if(aitIsPluginActive('toolkit')){
			$cptId = substr($type, 4);

			$cpt = aitManager('cpts')->get($cptId);

			if($isCpt and $cpt){
				$metaDefaults = $cpt->getMetabox($metaboxId)->getConfigDefaults();
				if($postmeta == '' and is_array($metaDefaults[$metaboxKey])) $postmeta = array();
				$postmeta = array_replace_recursive($metaDefaults[$metaboxKey], $postmeta);
				$postmeta = apply_filters('ait-wplatte-post-meta', $postmeta, $cpt, $cptId, $metaboxKey, $key, $metaDefaults);
				array_walk_recursive($postmeta, array(__CLASS__, 'filterPostmetaValue'));
			}
		}

		return $postmeta;
	}



	public static function filterPostmetaValue(&$item, $key)
	{
		$item = apply_filters('ait-filter-value-for-current-locale', $item, $key);
	}



	public static function menuArgs($location, $args)
	{
		$args['show_home'] = true;
		$args['container_class'] = 'nav-menu-container';

		if($args['theme_location'])
			$args['container_class'] .= ' nav-menu-' . $args['theme_location'];

		$args['menu_class'] = 'nav-menu clear';
		$args['fallback_cb'] = array(__CLASS__, 'menuFallback');
		return $args;
	}



	/**
	 * Customized wp_page_menu() function
	 * @param  array $args Args
	 * @return string      HTML of the menu
	 */
	public static function menuFallback($args)
	{

		$defaults = array(
			'sort_column' => 'menu_order, post_title',
			'menu_class' => 'menu'
		);

		$args = wp_parse_args($args, $defaults);

		$menu = '';

		unset($args['walker']);
		$list_args = $args;

		// Show Home in the menu
		if(!empty($args['show_home'])){
			if($args['show_home'] === true or $args['show_home'] === '1'  or $args['show_home'] === 1)
				$text = __('Home', 'default');
			else
				$text = $args['show_home'];

			$class = '';

			if(is_front_page() && !is_paged())
				$class = 'class="current_page_item"';

			$menu .= sprintf('<li %s><a href="%s" title="%s">%s</a></li>',
				$class,
				home_url('/'),
				esc_attr($text),
				$text
			);

			// If the front page is a page, add it to the exclude list
			if(get_option('show_on_front') == 'page'){
				if(!empty( $list_args['exclude'])){
					$list_args['exclude'] .= ',';
				}else{
					$list_args['exclude'] = '';
				}
				$list_args['exclude'] .= get_option('page_on_front');
			}
		} // home


		$list_args['echo'] = false;
		$list_args['title_li'] = '';
		$menu .= str_replace( array( "\r", "\n", "\t" ), '', wp_list_pages($list_args));


		if($menu)
			$menu = '<ul class="' . esc_attr($args['menu_class']) . '">' . $menu . '</ul>';

		$containerClass = esc_attr($args['container_class']);

		$menu = '<div class="' . $containerClass . '">' . $menu . "</div>\n";

		$menu = apply_filters('wp_page_menu', $menu, $args);

		echo $menu;
	}



	public static function addAitCpts($cpts)
	{
		return get_post_types(array('ait-cpt' => true));
	}



	public static function addAitTaxs($taxs)
	{
		return get_taxonomies(array('ait-tax' => true));
	}



	public static function addLangToCustomWpQuery($query, $originalArgs)
	{
		global $polylang;
		if(isset($polylang) and isset($query['post_type'])){
			$translatableCpts = get_post_types(array('ait-translatable-cpt' => true));
			if(isset($query['tax_query']) and aitOptions()->isQueryForSpecialPage()){
				foreach($query['tax_query'] as $i => $taxquery){
					$newTermId = pll_get_term($taxquery['terms']);
					if($newTermId){
						$query['tax_query'][$i]['terms'] = $newTermId;
					}
				}
			}
			if(in_array($query['post_type'], $translatableCpts) and in_array($query['post_type'], $polylang->options["post_types"])){
				$query['lang'] = AitLangs::getCurrentLanguageCode();
			}
		}
		return $query;
	}


}
