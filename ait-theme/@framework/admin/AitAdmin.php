<?php

class AitAdmin
{

	/**
	 * Generated Menu slugs
	 * @var array
	 */
	protected static $pagesSlugs = array();

	protected static $topLevelAdminPageSlug = '';


	public static function run()
	{
		AitWpAdminExtensions::register();

		add_action('admin_init', array(__CLASS__, 'onAdminInit'));

		AitShortcodesGenerator::register();

		if(!AitUtils::isAjax()){
			add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAdminCssAndJs'), 24);

			add_action('admin_menu', array(__CLASS__, 'renderAdminMenu'), 1);

			add_filter( 'custom_menu_order', '__return_true' );

			add_filter('menu_order', array(__CLASS__, 'changeMenuOrder'), 0);

			add_action('load-themes.php', array(__CLASS__, 'activateTheme'));
			add_action('switch_theme', array(__CLASS__, 'deactivateTheme'));

			add_action('pll_after_add_language', array(__CLASS__, 'onAfterAddLanguage'));

			add_action('admin_print_styles', array(__CLASS__, 'highlightAitMenuItems'));

			add_action('media_buttons', array(__CLASS__, 'addPageBuilderButton'), 100, 1);

			add_filter('redirect_post_location', array(__CLASS__, 'redirectToPageBuilder'), 10, 2);

			add_action('admin_notices', array(__CLASS__, 'membershipNotice'));

			self::modifyPageRowActions();

		}
	}


	public static function membershipNotice()
	{
		if(AIT_THEME_PACKAGE !== 'basic') return;

		$assetsImgUrl = aitPaths()->url->admin . '/assets/img/';
		$ctaUrl = "https://www.ait-themes.club/full-membership/?utm_source=wp-admin&utm_medium=wp-admin-banner&utm_campaign=Free-Theme";

		?>
		<div id="ait-membership-notice" class="ait-notice ait-basic-package-notice" onclick="jQuery('#ait-membership-notice').toggleClass('active')">
			<div class="text"><strong class="big"><?php _e('Download premium extensions', 'ait-admin') ?></strong></div>
			<button type="button" class="ait-notice-button white uppercase has-arrow"><?php _e('Learn More', 'ait-admin') ?></button>
		</div>

		<div class="ait-notice-accordion">
			<div class="ait-notice-accordion-cols">
				<a href="<?php echo $ctaUrl ?>" target="_blank" class="ait-notice-accordion-item green">
					<div class="img"><img src="<?php echo $assetsImgUrl ?>ait-banner-extensions.png"></div>
					<h3><i class="dashicons dashicons-schedule"></i><span><?php _e('Tons of clever extensions', 'ait-admin') ?></span></h3>
				</a>
				<a href="<?php echo $ctaUrl ?>" target="_blank" class="ait-notice-accordion-item blue">
					<div class="img"><img src="<?php echo $assetsImgUrl ?>ait-banner-updates.png"></div>
					<h3><i class="dashicons dashicons-upload"></i><span><?php _e('Continual security updates & new features', 'ait-admin') ?></span></h3>
				</a>
				<a href="<?php echo $ctaUrl ?>" target="_blank" class="ait-notice-accordion-item orange">
					<div class="img"><img src="<?php echo $assetsImgUrl ?>ait-banner-support.png"></div>
					<h3><i class="dashicons dashicons-sos"></i><span><?php _e('World class support service', 'ait-admin') ?></span></h3>
				</a>
			</div>
			<div class="ait-notice-accordion-footer">
				<div class="ait-notice-button-group">
					<span class="ait-notice-button-hint"><?php _e('Remove this ad', 'ait-admin') ?></span>
					<a href="<?php echo $ctaUrl ?>" target="_blank" class="ait-notice-button positive"><?php printf(__('Upgrade to Full Membership %s', 'ait-admin'), '<span class="dashicons dashicons-cart"></span>') ?></a>
				</div>
			</div>
		</div>
		<?php
	}


	public static function highlightAitMenuItems()
	{
		$usedColorName = get_user_option('admin_color');

		$colors = array(
			'fresh'     => '#090909',
			'blue'      => '#3290B1',
			'coffee'    => '#3C3632',
			'ectoplasm' => '#352946',
			'light'     => '#bbb',
			'midnight'  => '#16181A',
			'ocean'     => '#526469',
			'sunrise'   => '#b43c38',
		);

		if(isset($colors[$usedColorName])){
			$color = $colors[$usedColorName];
			$adminPages = aitConfig()->getAdminConfig('pages');
			$css = '';

			foreach($adminPages as $page){
				$css .= "li#toplevel_page_ait-{$page['slug']} > a { background: {$color}; }";
			}

			echo "<style>$css</style>";
		}
	}



	public static function onAdminInit()
	{
		if(AitUtils::isAjax()){
			AitAdminAjax::register();
		}
	}



	public static function onAfterAddLanguage()
	{
		AitCache::clean();
	}



	/**
	 * Generates AIT Admin menu
	 */
	public static function renderAdminMenu()
	{
		$t = aitOptions()->getOptionsByType('theme');

		$iconUrl = isset($t['adminBranding']['adminMenuIcon']) ? $t['adminBranding']['adminMenuIcon'] : aitPaths()->url->admin . '/assets/img/ait-admin-menu-icon16.png';
		$adminMenuTitle = isset($t['adminBranding']['adminTitle']) ? AitLangs::getCurrentLocaleText($t['adminBranding']['adminTitle'], esc_html__('Theme Admin', 'ait-admin')) : esc_html__('Theme Admin', 'ait-admin');

		$aitAdminItemsPosition = 40.01; // position index of separator, 25 = Comments, 26 - 59 = free, 60 = second separator
		$adminPages = aitConfig()->getAdminConfig('pages');

		global $menu;

		$menu[] = array('', 'read', 'ait-separator1', '', 'wp-menu-separator ait-separator');
		$menu[] = array('', 'read', 'ait-separator2', '', 'wp-menu-separator ait-separator');

		// Add sub pages
		foreach($adminPages as $page){
			$class = AitUtils::id2class($page['slug'], 'Page', 'AitAdmin');
			$pageObject = new $class($page['slug']);

			$pageHook = add_menu_page(
				($page['slug'] == 'theme-options') ? $adminMenuTitle : $page['menu-title'],
				($page['slug'] == 'theme-options') ? $adminMenuTitle : $page['menu-title'],
				apply_filters('ait-admin-pages-permission', 'manage_options', $page),
				"ait-{$page['slug']}",
				array($pageObject, "renderPage"),
				$iconUrl,
				(string) $aitAdminItemsPosition += 0.01
			);

			if(isset($page['sub']) and !empty($page['sub'])){

				// add one more time as submenu but with another title
				if($page['slug'] == 'theme-options'){
					$pageHook = add_submenu_page(
						"ait-{$page['slug']}",
						$page['menu-title'],
						$page['menu-title'],
						apply_filters('ait-admin-pages-permission', 'manage_options', $page),
						"ait-{$page['slug']}",
						array($pageObject, "renderPage")
					);
				}

				foreach($page['sub'] as $subpage){
					$class = AitUtils::id2class($subpage['slug'], 'Page', 'AitAdmin');
					if (isset($subpage['type']) && $subpage['type'] == 'plugin') {
						$pageObject = new AitAdminPluginOptionsPage($subpage);
					} else {
						$pageObject = new $class($subpage['slug']);
					}
					$pageHook = add_submenu_page(
						"ait-{$page['slug']}",
						$subpage['menu-title'],
						$subpage['menu-title'],
						apply_filters('ait-admin-pages-permission', 'manage_options', $subpage),
						"ait-{$subpage['slug']}",
						array($pageObject, "renderPage")
					);
					add_action('load-' . $pageHook, array($pageObject, "beforeRender"));
				}
			}
			add_action('load-' . $pageHook, array($pageObject, "beforeRender"));
		}
	}



	public static function changeMenuOrder($menuOrder)
	{
		$newOrder = $cpts = array();

		$adminPages = aitConfig()->getAdminConfig('pages');
		$slugs = wp_list_pluck($adminPages, 'slug');
		$firstSlug = array_shift($slugs);
		$lastSlug = array_pop($slugs);


		foreach($menuOrder as $i => $item){
			if(AitUtils::contains($item, $firstSlug)){
				$newOrder[] = 'ait-separator1';
				$newOrder[] = $item;
			}elseif(AitUtils::contains($item, $lastSlug)){
				$newOrder[] = $item;
				$newOrder[] = 'ait-separator2';
			}elseif(AitUtils::startsWith($item, 'edit.php?post_type=ait-')){
				$cpts["x$i"] = $item; // x is for insertAfter, because it overrides same indexes
			}elseif($item != 'ait-separator1' and $item != 'ait-separator2'){
				$newOrder[] = $item;
			}
		}

		$lastAitSepIndex = array_search('ait-separator2', $newOrder);

		if(!empty($cpts)){
			NArrays::insertAfter($newOrder, $lastAitSepIndex, $cpts); // insert all our cpts after our main menu items
			$newOrder = array_values($newOrder); // get rid off of "x{...}" indexes
		}else{
			unset($newOrder[$lastAitSepIndex]);
		}

		return $newOrder;
	}



	public static function getCurrentPageSlug()
	{
		$id = get_current_screen()->id;
		$adminPages = aitConfig()->getAdminConfig('pages');
		$return = '';

		foreach($adminPages as $page){
			if(AitUtils::endsWith($id, $page['slug'])){
				$return = $page['slug'];
				break;
			}
			if(isset($page['sub'])){
				foreach($page['sub'] as $subpage){
					if(AitUtils::endsWith($id, $subpage['slug'])){
						$return = $subpage['slug'];
						break;
					}
				}
			}
		}

		return $return;
	}



	/**
	 * Activates theme. Saves default options.
	 * @return void
	 */
	public static function activateTheme()
	{
		global $pagenow;

		if($pagenow == 'themes.php' and (isset($_GET['activated']) or isset($_GET['ait-theme-continue']))){

			AitCache::clean();

			$new = aitConfig()->extractDefaultsFromConfig(
				aitConfig()->getRawConfig(),
				true
			);

			foreach(AitConfig::getMainConfigTypes() as $configType){
				$key = aitOptions()->getOptionKey($configType);
				$wasAdded = add_option($key, $new[$configType]);
			}

			if(@is_writable(WP_PLUGIN_DIR)){
				AitAutomaticPluginInstallation::run();
			}

			do_action('ait-theme-activation');

			flush_rewrite_rules();

			if($wasAdded){ // this is first time activation of this theme, so redirect to Importing Demo Content admin page
				$redirectTo = add_query_arg(array('page' => 'ait-backup#ait-backup-import-demo-content-panel'), admin_url("admin.php"));
			}else{
				$redirectTo = admin_url('themes.php');
			}

			aitManager('assets')->compileLessFiles();

			wp_redirect(esc_url_raw($redirectTo));
		}
	}



	/**
	 * Deactivate theme
	 * @return void
	 */
	public static function deactivateTheme()
	{
		flush_rewrite_rules();
	}



	/**
	 * Registers JavaScripts for Ait Admin
	 */
	public static function enqueueAdminCssAndJs()
	{
		global $pagenow;

		$assetsUrl = aitPaths()->url->admin . '/assets';

		wp_enqueue_style('ait-wp-admin-style', "{$assetsUrl}/css/wp-admin.css", array('media-views'), AIT_THEME_VERSION);

		self::pageBuilderTutorial();

		$pages = array('edit.php', 'post-new.php', 'post.php', 'media-upload.php', 'nav-menus.php', 'profile.php', 'user-edit.php');

		if(self::getCurrentPageSlug() or in_array($pagenow, $pages) or apply_filters('ait-enqueue-admin-assets', false)){

			$langCode = AitLangs::getCurrentLanguageCode();

			$min = ((defined('SCRIPT_DEBUG') and SCRIPT_DEBUG) or AIT_DEV) ? '' : '.min';

			wp_enqueue_style('ait-colorpicker', "{$assetsUrl}/libs/colorpicker/colorpicker.css", array(), '2.2.1');
			wp_enqueue_style('ait-jquery-chosen', "{$assetsUrl}/libs/chosen/chosen.css", array(), '0.9.10');
			wp_enqueue_style('jquery-ui', "{$assetsUrl}/libs/jquery-ui/jquery-ui.css", array('media-views'), AIT_THEME_VERSION);
			wp_enqueue_style('ait-jquery-timepicker-addon', "{$assetsUrl}/libs/timepicker-addon/jquery-ui-timepicker-addon{$min}.css", array(), AIT_THEME_VERSION);
			wp_enqueue_style('jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch.css", array(), '0.4.1');

			wp_enqueue_style('ait-admin-style', "{$assetsUrl}/css/style.css", array('media-views'), AIT_THEME_VERSION);
			wp_enqueue_style('ait-admin-options-controls', "{$assetsUrl}/css/options-controls" . ($pagenow == 'edit.php' ? "-quickedit" : "") . ".css", array('ait-admin-style', 'ait-jquery-chosen'), AIT_THEME_VERSION);

			$fontCssFile = aitUrl('css', '/libs/font-awesome.min.css');
			if($fontCssFile){
				wp_enqueue_style('ait-font-awesome-select', $fontCssFile, array(), '4.2.0');
			}

			/* remove easyreservations styles */
			wp_dequeue_style('myStyleSheets');
			wp_dequeue_style('chosenStyle');
			/* remove easyreservations styles */

			wp_enqueue_script('ait.admin', "{$assetsUrl}/js/ait.admin.js", array('media-editor'), AIT_THEME_VERSION, TRUE);

			self::adminGlobalJsSettings();

			// js libs
			wp_register_script('ait-jquery-filedownload', "{$assetsUrl}/libs/file-download/jquery.fileDownload{$min}.js", array('jquery', 'ait.admin'), '1.3.3', TRUE);

			wp_enqueue_script('ait-colorpicker', "{$assetsUrl}/libs/colorpicker/colorpicker{$min}.js", array('jquery'), '2.2.1', TRUE);
			wp_enqueue_script('ait-jquery-ui-touch', "{$assetsUrl}/libs/jquery-touch-punch/jquery.ui.touch-punch{$min}.js", array('jquery'), '0.2.3', TRUE);
			wp_enqueue_script('ait-jquery-chosen', "{$assetsUrl}/libs/chosen/chosen.jquery{$min}.js", array('jquery'), '1.0.0', TRUE);
			wp_enqueue_script('ait-jquery-sheepit', "{$assetsUrl}/libs/sheepit/jquery.sheepItPlugin{$min}.js", array('jquery', 'ait.admin'), '1.1.1-ait-1', TRUE);
			wp_enqueue_script('ait-jquery-deparam', "{$assetsUrl}/libs/jquery-deparam/jquery-deparam{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script('ait-jquery-rangeinput', "{$assetsUrl}/libs/rangeinput/rangeinput.min.js", array('jquery', 'ait.admin'), '1.2.7', TRUE);
			wp_enqueue_script('ait-jquery-numberinput', "{$assetsUrl}/libs/numberinput/numberinput{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script('ait-jquery-truncate', "{$assetsUrl}/libs/jquery-truncate/jquery.truncate{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);

			wp_enqueue_script('ait-jquery-timepicker-addon', "{$assetsUrl}/libs/timepicker-addon/jquery-ui-timepicker-addon{$min}.js", array('jquery', 'ait.admin', 'jquery-ui-slider', 'jquery-ui-datepicker'), FALSE, TRUE);

			if($langCode !== 'en'){
				wp_enqueue_script('ait-jquery-datepicker-translation', "{$assetsUrl}/libs/datepicker/jquery-ui-i18n{$min}.js", array('jquery', 'ait.admin', 'jquery-ui-datepicker'), FALSE, TRUE);
				wp_enqueue_script('ait-jquery-timepicker-translation', "{$assetsUrl}/libs/timepicker-addon/jquery-ui-timepicker-addon-i18n{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			}

			wp_enqueue_script('ait-jquery-switch', "{$assetsUrl}/libs/jquery-switch/jquery.switch{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);
			wp_enqueue_script('ait-bootstrap-dropdowns', "{$assetsUrl}/libs/bootstrap-dropdowns/bootstrap-dropdowns{$min}.js", array('jquery', 'ait.admin'), FALSE, TRUE);

			$t = aitOptions()->getOptionsByType('theme');
			$gmapsApiKey = empty($t['google']['mapsApiKey']) ? "" : $t['google']['mapsApiKey'];
			wp_enqueue_script('ait-google-maps', "//maps.google.com/maps/api/js?key={$gmapsApiKey}&language=". AitLangs::getGmapsLang(), array('jquery'), FALSE, TRUE);
			wp_enqueue_script('ait-jquery-gmap3', "{$assetsUrl}/libs/gmap3/gmap3.min.js", array('jquery', 'ait.admin', 'ait-google-maps'), FALSE, TRUE);

			wp_enqueue_script('ait-leaflet', "{$assetsUrl}/libs/leaflet/leaflet.js", array(), FALSE, TRUE);
			wp_enqueue_style( 'ait-leaflet', "{$assetsUrl}/libs/leaflet/leaflet.css");
			
			wp_enqueue_script('ait-jquery-raty', "{$assetsUrl}/libs/raty/jquery.raty-2.5.2.js", array('jquery'), '2.5.2', TRUE);

			wp_enqueue_media();

			wp_enqueue_script('ait.admin.Tabs', "{$assetsUrl}/js/ait.admin.tabs.js", array('ait.admin', 'jquery'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.options', "{$assetsUrl}/js/ait.admin.options.js", array('ait.admin', 'jquery', 'jquery-ui-tabs', 'ait-jquery-chosen', 'jquery-ui-datepicker', 'ait-jquery-gmap3'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.backup', "{$assetsUrl}/js/ait.admin.backup.js", array('ait.admin', 'jquery', 'ait-jquery-filedownload'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.options.elements', "{$assetsUrl}/js/ait.admin.options.elements.js", array('ait.admin', 'ait.admin.options', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'), AIT_THEME_VERSION, TRUE);
			wp_enqueue_script('ait.admin.nav-menus', "{$assetsUrl}/js/ait.admin.nav-menus.js", array('ait.admin', 'ait.admin.options', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'), AIT_THEME_VERSION, TRUE);
		}
	}



	public static function adminGlobalJsSettings()
	{
		$u = wp_upload_dir();

		$settings = array(
			'ajax' => array(
				'url'     => admin_url('admin-ajax.php'),
				'actions' => array(),
			),
			'currentPage' => self::getCurrentPageSlug(),
			'paths' => array(
				'root'      => aitPaths()->url->root,
				'theme'     => aitPaths()->url->theme,
				'wpcontent' => content_url(),
				'uploads'   => $u['baseurl'],
			),
			'l10n' => array(
				'save' => array(
					'working' => __('&hellip; saving &hellip;', 'ait-admin'),
					'done'    => esc_html__('settings were saved successfully', 'ait-admin'),
					'error'   => esc_html__('there was an error during saving', 'ait-admin'),
				),
				'reset' => array(
					'working' => __('Resetting&hellip;', 'ait-admin'),
					'done'    => __('Successfully reset. This page will reload&hellip;', 'ait-admin'),
				),
				'confirm' => array(
					'removeElement'       => esc_html__('Are you sure you want to remove this element?', 'ait-admin'),
					'removeCustomOptions' =>  esc_html__('Are you sure you want to delete custom page options of this page?', 'ait-admin'),
					// translators: {pageTtile} is placeholder like %s
					'addCustomOptions'    => __("You are about to create custom options for page:\n{pageTitle}.\nIs this ok?", 'ait-admin'),
				),
				'datetimes' => array(
					'dateFormat'  => AitUtils::phpDate2jsDate(get_option('date_format')),
					// 'timeFormat'  => get_option('time_format'),
					'startOfWeek' => get_option('start_of_week'),
				),
				'labels' => array(
					'settingsForSpecialPageType' => esc_html__("Special page", 'ait-admin'),
					'settingsForStandardPageType' => esc_html__("Standard page", 'ait-admin'),
				),
				'elementUserDescriptionPlaceholder' => esc_html__('Click to add custom description', 'ait-admin'),
				'backup' => array(
					'info' => array(
						'noBackupFile'       => esc_html__('Please select backup file', 'ait-admin'),
						// translators: {option} and {filename} are placeholders like %s
						'selectedBadFileFix' => __("You selected option '{option}' but file was '{filename}'. We corrected this for you :)", 'ait-admin'),
						'importBackup'       => esc_html__('Are you sure you want to import backup? All tables will be truncated before import!', 'ait-admin'),
						'importDemoContent'  => esc_html__('Are you sure you want to import demo content? Whole content of your website will be replaced!', 'ait-admin'),
					),
					'import' => array(
						'working' => esc_html__("Importing...", 'ait-admin'),
						'done'    => esc_html__('Importing is done. Check out the report.', 'ait-admin'),
						'error'   => esc_html__('There was an error during importing. Check out the report.', 'ait-admin'),
					),
					'export' => array(
						'working' => esc_html__("Exporting...", 'ait-admin'),
						'done'    => esc_html__('You just got a file download dialog or ribbon.', 'ait-admin'),
						'error'   => esc_html__('Your file download failed. Please try again.', 'ait-admin'),
					)
				)
			),
		);

		$class = 'AitAdminAjax';

		$methods = get_class_methods($class);
		$r = new NClassReflection($class);

		foreach($methods as $method){
			if($r->getMethod($method)->getAnnotation('WpAjax') === true){
				$settings['ajax']['actions'][$method] = "admin:{$method}";
			}
		}


		wp_localize_script('ait.admin', 'AitAdminJsSettings', apply_filters('ait-admin-global-js-settings', $settings));
	}



	public static function pageBuilderTutorial()
	{
		$screen = 'toplevel_page_ait-pages-options';

		$pointers = array(
			array(
				'id' => 'ait-pb-1', // unique id for this pointer
				'screen' => $screen, // this is the page hook we want our pointer to show on
				'target' => '.full-pagebuilder #ait-page-options-selection', // the css selector for the pointer to be tied to, best to use ID's
				'title' => __('Page Select', 'ait-admin'),
				'content' => __('This panel indicates the page you are currently editing. To edit different one, you can click on this dropdown and pick from the list of all pages.', 'ait-admin'),
				'position' => array(
					'edge' => 'top', //top, bottom, left, right
					'align' => 'middle' //top, bottom, left, right, middle
				)
			),
			array(
				'id' => 'ait-pb-2',
				'screen' => $screen,
				'target' => '.ait-custom-header-tools',
				'title' => __('Page Tools', 'ait-admin'),
				'content' => __('Here you can find useful tools to manage your pages. E.g. import options from different page or quick view of the current one.', 'ait-admin'),
				'position' => array(
					'edge' => 'top',
					'align' => 'left'
				)
			),
			array(
				'id' => 'ait-pb-3',
				'screen' => $screen,
				'target' => '#ait-available-elements',
				'title' => __('Page Builder Elements', 'ait-admin'),
				'content' => __('This is the list of all available elements which you can click on or drag and drop to add to your page. Elements are categorized as following: Columnable and Fullwidth elements. Columnable ones can be dropped also right into Columns element.', 'ait-admin'),
				'position' => array(
					'edge' => 'top',
					'align' => 'left'
				)
			),
			array(
				'id' => 'ait-pb-4',
				'screen' => $screen,
				'target' => '#ait-used-elements-sortable-wrapper',
				'title' => __('Drop your elements here', 'ait-admin'),
				'content' => __('Elements are dropped into this area. You can sort and edit these elements.', 'ait-admin'),
				'position' => array(
					'edge' => 'bottom',
					'align' => 'middle'
				)
			),
			array(
				'id' => 'ait-pb-5',
				'screen' => $screen,
				'target' => '#ait-used-elements-unsortable',
				'title' => __('Unsortable Elements', 'ait-admin'),
				'content' => __('These elements can be fully edited but not sorted. They have their own place in the content of your page. Like head should be always on top of your body, right? ;)', 'ait-admin'),
				'position' => array(
					'edge' => 'bottom',
					'align' => 'middle'
				)
			),
			array(
				'id' => 'ait-pb-6',
				'screen' => $screen,
				'target' => '#ait-layout-options',
				'title' => __('Page Layout Options', 'ait-admin'),
				'content' => __('You guess right, if you think these options are important. As they are on the very top and separated. They control the overall layout of your page like sidebars or footer.', 'ait-admin'),
				'position' => array(
					'edge' => 'top',
					'align' => 'left'
				)
			),
			array(
				'id' => 'ait-pb-7',
				'screen' => $screen,
				'target' => '.ait-header-save',
				'title' => __('Save your changes', 'ait-admin'),
				'content' => __("And at last behold the save button. It's pretty big so you shouldn't forget to save your changes. So good luck in building!", 'ait-admin'),
				'position' => array(
					'edge' => 'top',
					'align' => 'right'
				)
			),
		);

       //Now we instantiate the class and pass our pointer array to the constructor
       $aitPointers = new WP_Help_Pointer($pointers, true);
	}



	public static function addPageBuilderButton($editorId)
	{
		$s = get_current_screen();
		$post = get_post();

		if($post and $post->post_type == 'page' and $s->id == 'page'){
			printf(
				'<a href="#" id="ait-goto-page-builder-button" class="button button-primary" data-ait-empty-title-note="%s">%s</a>',
				esc_html__('Please enter title of the page', 'ait-admin'),
				esc_html__('Save and Open in Page Builder', 'ait-admin')

			);
		}
	}



	public static function modifyPageRowActions()
	{
		add_filter('page_row_actions', array(__CLASS__, 'addPageBuilderLinkToPageRowActions'), 10, 2);
	}



	public static function addPageBuilderLinkToPageRowActions($actions, $page)
	{
		$args = array('page' => 'pages-options', 'oid' => '_page_' . $page->ID);

		if($page->post_status != 'auto-draft'){
			if(get_option('show_on_front') == 'page'){
				if($b = get_option('page_for_posts')){
					$blog = (int) $b;
					if($page->ID == $blog)
						$args['oid'] = "_blog";
				}
			}


			$title = esc_html__('Page Builder', 'ait-admin');
			$args['oidnonce'] = AitUtils::nonce('oidnonce');

			$url = esc_url(AitUtils::adminPageUrl($args));

			$link = "<a href=\"$url\">$title</a>";

			$actions['page_builder'] = $link;
		}

		return $actions;
	}




	public static function redirectToPageBuilder($location, $postId)
	{
		if(!isset($_POST['ait-redirect-to-page-builder']) or $_POST['post_type'] != 'page'){
			return $location;
		}

		$args = array('page' => 'pages-options', 'oid' => '_page_' . $postId);

		$r = aitOptions()->getLocalOptionsRegister();
		$blogId = 0;

		if(get_option('show_on_front') == 'page'){
			if($b = get_option('page_for_posts')){
				$blogId = (int) $b;
				if($postId == $blogId){
					$args['oid'] = "_blog";
				}
			}
		}

		if(!in_array("_page_{$postId}", $r['pages']) and $postId != $blogId){
			$args['oidnonce'] = AitUtils::nonce('oidnonce');
		}

		$url = AitUtils::adminPageUrl($args);
		return esc_url_raw($url);
	}
}
