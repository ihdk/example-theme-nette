<?php


/**
 * Does upgrade jobs
 */
class AitUpgrader
{

	protected static $skeletonVersionOptionKey;
	protected static $themeVersionOptionKey;
	protected static $parentThemeVersionOptionKey;

	protected $errors = array();



	public static function run()
	{
		add_action('admin_init', array(__CLASS__, 'maybeDoUpgradeOnAdminInit'));
		add_action('ait-after-import', array(__CLASS__, 'maybeDoUpgradeAfterImport'), 10, 2);
		add_action('ait-theme-activation', array(__CLASS__, 'addVersionsOnThemeActivation'));
	}



	protected static function setKeys()
	{
		$parentTheme = wp_get_theme()->parent();
		$pt = $parentTheme ? $parentTheme->template : AIT_CURRENT_THEME;

		$oldSkeletonV = get_option("_ait_skeleton_version", '');
		if($oldSkeletonV !== ''){
			add_option("_ait_" . AIT_CURRENT_THEME . "_skeleton_version", $oldSkeletonV);
			delete_option("_ait_skeleton_version");
		}

		$oldThemeV = get_option("_ait_theme_version", '');
		if($oldThemeV !== ''){
			add_option("_ait_" . AIT_CURRENT_THEME . "_theme_version", $oldThemeV);
			delete_option("_ait_theme_version");
		}

		self::$skeletonVersionOptionKey = "_ait_" . AIT_CURRENT_THEME . "_skeleton_version";

		self::$parentThemeVersionOptionKey = "_ait_" . $pt . "_parent_theme_version";
		self::$themeVersionOptionKey = "_ait_" . AIT_CURRENT_THEME . "_theme_version";
	}



	public static function maybeDoUpgradeOnAdminInit()
	{
		if(AitUtils::isAjax() or defined('IFRAME_REQUEST')) return;

		$upgrader = new self;

		self::setKeys();

		$upgrader->maybeDoUpgrade();
	}



	public static function addVersionsOnThemeActivation()
	{
		self::setKeys();

		if(get_option(self::$skeletonVersionOptionKey, '') === ''){
			add_option(self::$skeletonVersionOptionKey, AIT_SKELETON_VERSION);
		}

		if(get_option(self::$themeVersionOptionKey, '') === ''){
			add_option(self::$themeVersionOptionKey, AIT_THEME_VERSION);
		}

		if(get_option(self::$parentThemeVersionOptionKey, '') === ''){
			$parentTheme = wp_get_theme()->parent();
			$v = $parentTheme ? $parentTheme->version : AIT_THEME_VERSION;
			add_option(self::$parentThemeVersionOptionKey, $v);
		}
	}



	public function maybeDoUpgrade()
	{
		if(version_compare($this->getSkeletonVersion(), AIT_SKELETON_VERSION, '<')){
			$this->skeletonUpgrade();

			if($this->noErrors()){
				$this->updateSkeletonVersionToNewest();
				AitCache::clean();
			}
		}

		$parentTheme = wp_get_theme()->parent();
		$v = $parentTheme ? $parentTheme->version : AIT_THEME_VERSION;

		if(!$this->parentThemeVersionOptionKeyExists()){
			$this->themeUpgrade();

			if($this->noErrors()){
				$this->updateParentThemeVersionToNewest();
				$this->updateThemeVersionToNewest();
				AitCache::clean();
			}
		}elseif(version_compare($this->getParentThemeVersion(), $v, '<')){
			$this->themeUpgrade();

			if($this->noErrors()){
				$this->updateParentThemeVersionToNewest();
				$this->updateThemeVersionToNewest();
				AitCache::clean();
			}
		}

		if(is_child_theme() and version_compare($this->getThemeVersion(), AIT_THEME_VERSION, '<')){
			$this->themeUpgrade();

			if($this->noErrors()){
				$this->updateThemeVersionToNewest();
				AitCache::clean();
			}
		}
	}



	public function skeletonUpgrade()
	{
		if(version_compare(self::getSkeletonVersion(), '2.1.4', '<')){
			$upgrade = new AitSkeletonUpgrade21;
			$this->errors = $upgrade->execute();
		}

		if(version_compare(self::getSkeletonVersion(), '2.2.3', '<')){
			$upgrade = new AitSkeletonUpgrade223;
			$errors = $upgrade->execute();
			$this->errors = array_merge($this->errors, $errors);
			self::$skeletonVersionOptionKey = "_ait_" . AIT_CURRENT_THEME . "_skeleton_version";
			self::$themeVersionOptionKey = "_ait_" . AIT_CURRENT_THEME . "_theme_version";
		}

		if(version_compare(self::getSkeletonVersion(), '2.8.12', '<')){
			$upgrade = new AitSkeletonUpgrade2812;
			$this->errors = $upgrade->execute();
		}

		if(version_compare(self::getSkeletonVersion(), '2.9.8', '<')){
			$upgrade = new AitSkeletonUpgrade298;
			$this->errors = $upgrade->execute();
		}

		if(version_compare(self::getSkeletonVersion(), '2.9.9', '<')){
			$upgrade = new AitSkeletonUpgrade299;
			$this->errors = $upgrade->execute();
		}

		if(version_compare(self::getSkeletonVersion(), '2.9.13', '<')){
			$upgrade = new AitSkeletonUpgrade2913;
			$this->errors = $upgrade->execute();
		}

		if(version_compare(self::getSkeletonVersion(), '2.23.0', '<')){
			$upgrade = new AitSkeletonUpgrade2230;
			$this->errors = $upgrade->execute();
		}

		if(version_compare(self::getSkeletonVersion(), '2.23.30', '<')){
			$upgrade = new AitSkeletonUpgrade22330;
			$this->errors = $upgrade->execute();
		}

		do_action('ait-skeleton-upgrade', $this);

		$this->addAdminErrorNotices();
	}



	public function themeUpgrade()
	{
		do_action('ait-theme-upgrade', $this);

		$this->addAdminErrorNotices();
	}



	protected function noErrors()
	{
		return empty($this->errors);
	}



	public static function maybeDoUpgradeAfterImport($whatToImport, $sendResults)
	{
		if($whatToImport === 'demo-content'){
			$upgrade = new AitSkeletonUpgrade298;
			if($upgrade->needsUpgrade()){
				$upgrade->execute();
			}

			$upgrade = new AitSkeletonUpgrade299;
			if($upgrade->needsUpgrade()){
				$upgrade->execute();
			}

			$upgrade = new AitSkeletonUpgrade2913;
			$upgrade->execute();
		}
	}



	public function addErrors($maybeErrors = array())
	{
		if(is_callable($maybeErrors)){
			$errors = $maybeErrors();
		}else{
			$errors = $maybeErrors;
		}
		$this->errors = array_merge($this->errors, $errors);
	}



	protected function addAdminErrorNotices()
	{
		if(!empty($this->errors)){
			add_action('admin_notices', array($this, 'adminErrorNotices'));
		}
	}



	public function adminErrorNotices()
	{
		echo '<div class="error">';
		foreach($this->errors as $error){
			echo "<p>";
			echo esc_html($error);
			echo "</p>";
		}
		echo '</div>';
	}



	public function getSkeletonVersion()
	{
		return get_option(self::$skeletonVersionOptionKey, AIT_SKELETON_VERSION);
	}



	public function getParentThemeVersion()
	{
		$parentTheme = wp_get_theme()->parent();

		if($parentTheme){
			if($this->parentThemeVersionOptionKeyExists()){
				return get_option(self::$parentThemeVersionOptionKey);
			}else{
				return defined('AIT_UPGRADER_PREVIOUS_THEME_VERSION') ? AIT_UPGRADER_PREVIOUS_THEME_VERSION : '1.0';
			}
		}else{
			return $this->getThemeVersion();
		}
	}



	public function getThemeVersion()
	{
		return get_option(self::$themeVersionOptionKey, AIT_THEME_VERSION);
	}



	public function updateSkeletonVersionToNewest()
	{
		update_option(self::$skeletonVersionOptionKey, AIT_SKELETON_VERSION);
	}



	public function updateParentThemeVersionToNewest()
	{
		$parentTheme = wp_get_theme()->parent();
		$v = $parentTheme ? $parentTheme->version : AIT_THEME_VERSION;
		update_option(self::$parentThemeVersionOptionKey, $v);
	}



	public function updateThemeVersionToNewest()
	{
		update_option(self::$themeVersionOptionKey, AIT_THEME_VERSION);
	}



	public function parentThemeVersionOptionKeyExists()
	{
		return (get_option(self::$parentThemeVersionOptionKey, '') !== '');
	}
}
