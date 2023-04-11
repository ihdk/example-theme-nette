<?php


// ===============================================
// Paths
// -----------------------------------------------

/** @internal */
global $_aitPathsCache;
$_aitPathsCache = array(
	'parentUrl' => get_template_directory_uri(),
	'parentDir' => get_template_directory(),
	'childUrl'  => get_stylesheet_directory_uri(),
	'childDir'  => get_stylesheet_directory(),
	'design'    => array('css' => 1, 'js' => 1, 'img' => 1, 'fonts' => 1),
	'uploads'     => wp_upload_dir(),
);



/**
 * Initializes all predefined paths on first use,
 * then is returning all those paths
 * @return stdClass
 */
function aitPaths()
{
	static $_aitPaths;

	if(is_null($_aitPaths)){

		global $_aitPathsCache;

		$_aitPaths = new stdClass;

		$_aitPaths->url = (object) array(
			'root'    => home_url(),
			'theme'   => _aitSetPath("", "url"),
			'ait'     => _aitSetPath("/ait-theme", "url"),
			'css'     => _aitSetPath("/design/css", "url"),
			'js'      => _aitSetPath("/design/js", "url"),
			'img'     => _aitSetPath("/design/img", "url"),
			'fonts'   => _aitSetPath("/design/fonts", "url"),
			'cache'   => _aitSetCachePath('url'),
			'uploads' => $_aitPathsCache['uploads']['baseurl'],
			'assets'  => _aitSetPath("/ait-theme/assets", "url", true),
			'fw'      => _aitSetPath("/ait-theme/@framework", "url", true),
			'admin'   => _aitSetPath("/ait-theme/@framework/admin", "url", true),
			'vendor'  => _aitSetPath("/ait-theme/@framework/vendor", "url", true),
			'libs'    => _aitSetPath("/ait-theme/@framework/libs", "url", true),
		);

		$_aitPaths->dir = (object) array(
			'root'               => realpath(ABSPATH),
			'theme'              => _aitSetPath(""),
			'ait'                => _aitSetPath("/ait-theme"),
			'cptsMetaboxesConfig'=> _aitSetPath("/ait-theme/config/cpts-metaboxes"),
			'css'                => _aitSetPath("/design/css"),
			'js'                 => _aitSetPath("/design/js"),
			'fonts'              => _aitSetPath("/design/fonts"),
			'cache'              => _aitSetCachePath('dir'),
			'uploads'            => $_aitPathsCache['uploads']['basedir'],
			'langs'              => _aitSetPath("/ait-theme/languages"),
			'assets'             => _aitSetPath("/ait-theme/assets", "dir", true),
			'fw'                 => _aitSetPath("/ait-theme/@framework", "dir", true),
			'fwConfig'           => _aitSetPath("/ait-theme/@framework/config", "dir", true),
			'admin'              => _aitSetPath("/ait-theme/@framework/admin", "dir", true),
			'vendor'             => _aitSetPath("/ait-theme/@framework/vendor", "dir", true),
			'libs'               => _aitSetPath("/ait-theme/@framework/libs", "dir", true),
		);

		return $_aitPaths;

	}else{
		return $_aitPaths;
	}
}



/**
 * Gets absolute path to given file or dir
 * @param  string $dir  		Name of dir in ait-theme dir by default, otherwise name of dir in design dir or 'theme' for custom path from theme root
 * @param  string $path 		Given path to file or dir
 * @return string       		Absolute path to given file or dir
 */
function aitPath($dir, $path = '')
{
	global $_aitPathsCache;

	$baseDir = '/ait-theme/';

	if(isset($_aitPathsCache['design'][$dir])){
		$baseDir = '/design/';
	}

	if($dir == 'theme'){
		$baseDir = '';
		$dir = '';
	}

	$suffix = $baseDir . $dir . $path;

	if(isset($_aitPathsCache["childDir$suffix"])){
		return $_aitPathsCache["childDir$suffix"];
	}elseif(isset($_aitPathsCache["parentDir$suffix"])){
		return $_aitPathsCache["parentDir$suffix"];
	}


	if(file_exists($_aitPathsCache['childDir'] . $suffix)){

		$_aitPathsCache["childDir$suffix"] = $_aitPathsCache['childDir'] . $suffix;
		return $_aitPathsCache["childDir$suffix"];

	}elseif(file_exists($_aitPathsCache['parentDir'] . $suffix)){

		$_aitPathsCache["parentDir$suffix"] = $_aitPathsCache['parentDir'] . $suffix;
		return $_aitPathsCache["parentDir$suffix"];

	}

	return false;
}



/**
 * Gets full url to given file or dir
 * @param  string $dir  		Name of dir in ait-theme dir by default, otherwise name of dir in design dir or 'theme' for custom path from theme root
 * @param  string $path 		Given path to file or dir
 * @return string       		Absolute path to given file or dir
 */
function aitUrl($dir, $path = '')
{
	global $_aitPathsCache;

	$baseDir = '/ait-theme/';

	if(isset($_aitPathsCache['design'][$dir]))
		$baseDir = '/design/';

	if($dir == 'theme'){
		$baseDir = '';
		$dir = '';
	}

	$suffix = $baseDir . $dir . $path;

	if(isset($_aitPathsCache["childUrl$suffix"]))
		return $_aitPathsCache["childUrl$suffix"];
	elseif(isset($_aitPathsCache["parentUrl$suffix"]))
		return $_aitPathsCache["parentUrl$suffix"];


	if(file_exists($_aitPathsCache['childDir'] . $suffix)){

		$_aitPathsCache["childUrl$suffix"] = $_aitPathsCache['childUrl'] . $suffix;
		return $_aitPathsCache["childUrl$suffix"];

	}elseif(file_exists($_aitPathsCache['parentDir'] . $suffix)){

		$_aitPathsCache["parentUrl$suffix"] = $_aitPathsCache['parentUrl'] . $suffix;
		return $_aitPathsCache["parentUrl$suffix"];

	}

	return false;
}



/**
 * Gets absolute paths or urls to given file or dir in child and parent theme together
 * @param  string $dir  See $_aitPathsCache['libs'], $_aitPathsCache['design']
 * @param  string $path Given path to file or dir
 * @param  string $type url or path
 * @param  bool $returnChildAndParentPaths If true it returns all paths from child and skeleton too
 * @return string       Absolute paths or urls to given file or dir in child and in parent theme
 */
function aitGetPaths($dir, $path = '', $type = null, $returnChildAndParentPaths = false)
{
	global $_aitPathsCache;

	$return = array();

	$baseDir = 'ait-theme/';

	if(isset($_aitPathsCache['design'][$dir])){
		$baseDir = 'design/';
	}

	$suffix = "/" . $baseDir . $dir . $path;

	// child theme
	if(file_exists($_aitPathsCache['childDir'] . $suffix) or $returnChildAndParentPaths){
		$return['path'][] = $_aitPathsCache['childDir'] . $suffix;
		$return['url'][] = $_aitPathsCache['childUrl'] . $suffix;
	}

	// parent theme
	if(file_exists($_aitPathsCache['parentDir'] . $suffix) or $returnChildAndParentPaths){
		$return['path'][] = $_aitPathsCache['parentDir'] . $suffix;
		$return['url'][] = $_aitPathsCache['parentUrl'] . $suffix;
	}

	if(isset($return[$type])){
		return array_unique($return[$type]);
	}

	return array_unique($return);
}



/**
 * Sets properly path for a given item
 * @internal
 * @param  string  $path
 * @param  string  $type
 * @param  boolean $templateDir
 * @return string
 */
function _aitSetPath($path, $type = "dir", $templateDir = false)
{
	global $_aitPathsCache;

	if(file_exists($_aitPathsCache['childDir'] . $path) and !$templateDir)
		return ($type == 'dir') ? $_aitPathsCache['childDir'] . $path : $_aitPathsCache['childUrl'] . $path;
	else
		return ($type == 'dir') ? $_aitPathsCache['parentDir'] . $path : $_aitPathsCache['parentUrl'] . $path;
}



/**
 * Sets cache path to uploads dir even for multisite
 * @internal
 * @param  string $type 'dir' or 'url'
 * @return string       Absolute path to cache dir
 */
function _aitSetCachePath($type)
{
	global $_aitPathsCache;
	$s = '/cache/' . AIT_CURRENT_THEME;
	$dir = $_aitPathsCache['uploads']['basedir'] . $s;
	$url = set_url_scheme($_aitPathsCache['uploads']['baseurl'] . $s);

	if(!file_exists($dir)){
		wp_mkdir_p($dir);
		//@copy(dirname(__FILE__) . '/../../.htaccess', "$dir/.htaccess");
		@copy(get_template_directory() . '/.htaccess', "$dir/.htaccess");
	}

	return $type == 'dir' ? $dir : $url;
}
