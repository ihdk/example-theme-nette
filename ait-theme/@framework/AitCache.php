<?php


/**
 * Static wrapper for Nette Cache
 */
class AitCache
{
	/**
	 * Nette Cache object
	 * @var NCache
	 */
	protected static $cache;



	public function __construct()
	{
		throw new LogicException(__CLASS__ . ' is a static class. Can not be instantiate.');
	}



	public static function init()
	{
		self::$cache = new NCache(self::createCacheStorage(true), 'ait-theme');
	}



	public static function save($cacheKey, $data, $flags = array())
	{

		$f = array();
		if(isset($flags['files'])){
			$f[NCache::FILES] = $flags['files'];
		}

		if(isset($flags['tags'])){
			$f[NCache::TAGS] = $flags['tags'];
		}

		self::$cache->save(self::getFullCacheKey($cacheKey), $data, $f);
	}



	public static function load($cacheKey)
	{
		return self::$cache->load(self::getFullCacheKey($cacheKey));
	}



	public static function remove($cacheKey)
	{
		self::$cache->remove(self::getFullCacheKey($cacheKey));
	}



	public static function clean($flags = array())
	{
		if((isset($flags['less']) and $flags['less']) or count($flags) == 0){
			self::cleanLessCache();
			unset($flags['less']);
		}

		$f = array();

		if(empty($flags)){
			$f[NCache::ALL] = true;
		}

		if(isset($flags['tags'])){
			$f[NCache::TAGS] = $flags['tags'];
		}

		self::$cache->clean($f);
	}



	public static function cleanLessCache()
	{
		AitUtils::delete(aitPaths()->dir->cache, '.ht-*.less-cache');
		AitUtils::delete(aitPaths()->dir->cache, '*.css');
	}



	public static function cleanImageCache()
	{
		$u = WP_Thumb::uploadDir();
		$path = $u['basedir'] . '/cache/images';
		AitUtils::delete($path, "*");
	}



	public static function createCacheStorage($withJournal = false, $disableForRobotLoader = false)
	{
		$c = aitPaths()->dir->cache;

		if(defined('AIT_DISABLE_CACHE') and AIT_DISABLE_CACHE == true and !$disableForRobotLoader){
			return new NDevNullStorage;
		}

		if(is_writable($c)){
			if($withJournal)
				$storage = new NFileStorage($c, new NFileJournal($c));
			else
				$storage = new NFileStorage($c);
		}else{
			$storage = new NMemoryStorage;
		}

		return $storage;
	}



	protected static function getFullCacheKey($cacheKey)
	{
		global $wp_version;
		$cacheKey .= $wp_version . AIT_CURRENT_THEME . AIT_THEME_VERSION . AIT_SKELETON_VERSION;
		return $cacheKey;
	}

}
