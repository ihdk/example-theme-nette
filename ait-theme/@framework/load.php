<?php


/* $WCREV$ */
define('AIT_SKELETON_VERSION', '2.24.2');


if(file_exists(get_stylesheet_directory() . '/ait-theme/@theme.php')){
	include_once get_stylesheet_directory() . '/ait-theme/@theme.php';
}else{
	@include_once get_template_directory() . '/ait-theme/@theme.php'; // @ - intentionaly, may do not exists
}

define('AIT_CURRENT_THEME', sanitize_key(get_stylesheet()));

// assume that current active stylesheet (theme folder) is also theme codename
if(!defined('AIT_THEME_CODENAME')) define('AIT_THEME_CODENAME', AIT_CURRENT_THEME);
if(!defined('AIT_THEME_PACKAGE')) define('AIT_THEME_PACKAGE', 'developer');

define('AIT_THEME_VERSION', wp_get_theme()->version);

define('_AIT_FW_MEMORY', memory_get_peak_usage(true)); // for debugging


// === Check requirements after activating theme ====
//if(is_admin()) require_once dirname(__FILE__) . '/admin/check-requirements.inc';
if(is_admin()) require_once get_template_directory() . '/ait-theme/@framework/admin/check-requirements.inc';

// === Loads AIT version of Nette Framework ======
require_once get_template_directory() . '/ait-theme/@framework/vendor/nette-closures.min.inc';
require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

/*fw*/
// === Loads paths================================
//require_once dirname(__FILE__) . '/paths.php';
require_once get_template_directory() . '/ait-theme/@framework/paths.php';

// === Loads helpers functions ===================
require_once aitPaths()->dir->fw . '/functions.php';

// === Loads Ait Cache ==========================
require_once aitPaths()->dir->fw . '/AitCache.php';
/*fw*/

AitCache::init();

aitEnableDisableDevMode();

do_action('ait-after-framework-load');

// === Nette ========================
NHtml::$xhtml = false;

// === Class RobotLoader ========================
$loader = new NRobotLoader;
$loader->acceptFiles = '*.php, *.inc';
$loader->addDirectory(aitGetPaths('', '', 'path')); // ait-theme dir in parent & child
$loader->ignoreDirs = array('parts', 'design', 'assets', 'config', 'languages', 'plugins');
$loader->setCacheStorage(AitCache::createCacheStorage(false, true));
$loader->register();
unset($loader);
