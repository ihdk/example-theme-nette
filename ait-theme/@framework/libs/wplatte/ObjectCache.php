<?php


/**
 * Wrapper class for WordPress's object cache
 */
class WpLatteObjectCache
{

	protected static $group = 'wplatte';


	protected static function cacheKey($args)
	{
		return md5(serialize($args));
	}



	public static function save($key, $data, $args = array())
	{
		if($args){
			$args[] = $key;
			$key = self::cacheKey($args);
		}

		return wp_cache_add($key, $data, self::$group);
	}



	public static function load($key, $args = array())
	{
		if($args){
			$args[] = $key;
			$key = self::cacheKey($args);
		}

		return wp_cache_get($key, self::$group);
	}
}

