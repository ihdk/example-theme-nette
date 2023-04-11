<?php


class AitShortcodesGenerator extends NObject
{

	/**
	 * Available registered shortcodes
	 * @var array
	 */
	protected static $shortcodes = array();
	protected static $fullConfig = array('shortcodes' => array());
	protected static $attrsDefaults = array('shortcodes' => array());
	protected static $types = array();

	protected static $manager;



	/**
	 * Registers shortcodes generator
	 * @return void
	 */
	public static function register()
	{
		global $pagenow;

		// do not run Generator if plugin is not active
		if(!aitIsPluginActive('shortcodes'))
			return;

		// just register only on these pages
		if($pagenow != 'post-new.php' and $pagenow != 'post.php' and $pagenow != 'media-upload.php' and $pagenow != 'admin.php' and $pagenow != 'admin-ajax.php' and $pagenow != 'user-edit.php' and $pagenow != 'profile.php')
			return;

		self::$manager = AitShortcodesManager::getInstance(); // from AIT Shortcodes plugin

		add_action('admin_init', array(__CLASS__, 'onAdminInit'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueJs'));

	}



	public static function onAdminInit()
	{
		self::$shortcodes = self::$manager->getShortcodes();

		$rawOptions = array('shortcodes' => array());
		$attrs = array();

		foreach(self::$shortcodes as $sc => $o){
			$rawOptions['shortcodes'][$sc] = $o->getOptions();
			$rawOptions['shortcodes'][$sc]['text-domain'] = 'ait-shortcodes';
			self::$types[$sc] = $o->getType();
			$attrs['shortcodes'][$sc] = $o->getAttrs();
		}

		$result = aitConfig()->processConfig($rawOptions,false, 'shortcodes-full-config', array_values(self::$manager->getConfigFiles()));

		self::$fullConfig = $result['full-config']['shortcodes'];

		self::$attrsDefaults = array_replace_recursive($result['defaults']['shortcodes'], $attrs);

		if(get_user_option('rich_editing') == 'true'){
			if(!empty(self::$shortcodes))
				add_filter('mce_external_plugins', array(__CLASS__, 'addMceExternalPlugins'));
			add_filter('mce_buttons', array(__CLASS__, 'addMceButtons'));

			add_filter('media_upload_tabs', array(__CLASS__, 'mediaUploadTabs'));
			add_action("media_upload_ait-shortcodes", array(__CLASS__, 'renderGeneratorIframe'));
		}
	}



	public static function enqueueJs()
	{
		global $pagenow;
		$pages = array('edit.php', 'post-new.php', 'post.php', 'media-upload.php', 'nav-menus.php', 'profile.php', 'user-edit.php');

		if(AitAdmin::getCurrentPageSlug() or in_array($pagenow, $pages) or apply_filters('ait-enqueue-admin-assets', false)){
			wp_enqueue_script('ait.admin.shortcodes', aitPaths()->url->admin . "/assets/js/ait.admin.shortcodes.js", array('ait.admin', 'media', 'media-views'), AIT_THEME_VERSION, true);

			$o = array(
				'defaults' => self::$attrsDefaults['shortcodes'],
				'types' => self::$types,
			);

			wp_localize_script('ait.admin.shortcodes', 'AitShortcodes', $o);
		}
	}



	public static function mediaUploadTabs($tabs)
	{
		$tabs['ait-shortcodes'] = __('AIT Shortcodes', 'ait-admin');
		return $tabs;
	}



	public static function renderGeneratorIframe()
	{
		wp_enqueue_media();
		wp_iframe(array(__CLASS__, 'renderShortcodesForms'));
	}



	public static function renderShortcodesForms()
	{
		// fake it, till you make it :)))
		?>
		<div id="ait-shortcodes-options">

			<div class="media-frame-menu">
				<div class="ait-shortcodes-tabs">
					<div class="media-menu">
						<?php
						foreach(self::$shortcodes as $sc){
							if($sc->isChild()) continue;
							?>
							<a href="#ait-shortcode-<?php echo $sc->getName() ?>-panel" id="ait-shortcode-<?php echo $sc->getName() ?>-panel-tab" data-shortcode="<?php echo $sc->getName() ?>" class="media-menu-item"><?php echo $sc->getTitle() ?></a>
							<?php
						}
						?>
					</div>
				</div>
			</div>
			<?php

			?><div class="media-frame-content"><?php

			AitOptionControl::$useGroupKeyInNameAttr = false;
			add_filter('ait-langs-enabled', '__return_false');

			AitOptionsControlsRenderer::create(array(
				'configType'    => 'shortcodes',
				'adminPageSlug' => 'shortcode',
				'fullConfig'    => self::$fullConfig,
				'defaults'      => self::$attrsDefaults,
				'options'       => self::$attrsDefaults,
			))->render();

			?>
			</div>

			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-secondary"></div>
					<div class="media-toolbar-primary">
						<a href="#" id="ait-insert-shortcode" class="button media-button button-primary button-large media-button-select" data-shortcode=""><?php _e('Insert shortcode', 'ait-admin') ?></a>
					</div>
				</div>
			</div>

		</div>

		<?php
	}



	// ===============================================
	// TinyMCE
	// -----------------------------------------------

	/**
	 * Defins TinyMCE rich editor js plugin
	 * @return	void
	 */
	public static function addMceExternalPlugins($plugins)
	{
		$version = get_bloginfo('version');

		if ($version < 3.9) {
			$plugins['aitShortcodesButton'] = aitPaths()->url->admin . "/assets/js/tinymce-shortcodes-dropdown.js";
		} else {
			$plugins['aitShortcodesButton'] = aitPaths()->url->admin . "/assets/js/tinymce-shortcodes-dropdown-3.9.js";
		}



		return $plugins;
	}



	/**
	 * Adds TinyMCE rich editor buttons
	 * @return	void
	 */
	public static function addMceButtons($buttons)
	{
		$buttons[] = 'aitShortcodesButton';
		return $buttons;
	}

}
