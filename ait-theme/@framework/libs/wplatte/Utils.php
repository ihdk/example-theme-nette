<?php


/**
 * Utils class
 */
class WpLatteUtils
{


	/**
	 * Prefixes for custom post types and custom taxonomies
	 * @var array
	 */
	protected static $cptPrefixes = array(
		'post'     => '',
		'taxonomy' => '',
	);

	protected static $already = false;



	public static function addPrefix($type, $item)
	{
		if(empty($item))
			return $item;

		if(!self::$already){
			self::$cptPrefixes = apply_filters('wplatte-cpt-prefixes', self::$cptPrefixes);
			self::$already = true;
		}

		$prefix = self::$cptPrefixes[$type];

		if($type == 'taxonomy'){
			if(is_array($item)){
				foreach($item as $i => $tax){
					if(self::needsPrefix($tax, 'taxonomy')){
						$item[$i] = $prefix . $tax;
					}
				}
			}else{
				if(self::needsPrefix($item, 'taxonomy')){
					$item = $prefix . $item;
				}
			}

			return $item;
		}

		if($type == 'post'){
			if(is_array($item)){
				foreach($item as $i => $cpt){
					if(self::needsPrefix($cpt, 'post')){
						$item[$i] = $prefix . $cpt;
					}
				}
			}else{
				if(self::needsPrefix($item, 'post')){
					$item = $prefix . $item;
				}
			}

			return $item;
		}

	}



	public static function stripPrefix($type, $item)
	{
		if(!self::$already){
			self::$cptPrefixes = apply_filters('wplatte-cpt-prefixes', self::$cptPrefixes);
			self::$already = true;
		}

		$prefix = self::$cptPrefixes[$type];

		$len = strlen($prefix);

		if($len and strpos($item, $prefix) !== false)
			return substr($item, $len);
		else
			return $item;
	}



	/**
	 * Helper method for checking if given post type is custom
	 * @param  string  $type post type name
	 * @return boolean
	 */
	public static function isCpt($type)
	{
		$t = array('post' => true, 'page' => true, 'attachment' => true, 'revision' => true, 'nav_menu_item' => true);
		return !isset($t[$type]);
	}



	/**
	 * Helper method for checking if given taxonomy is custom
	 * @param  string  $tax taxonomy name
	 * @return boolean
	 */
	public static function isCustomTax($tax)
	{
		$t = array('category' => true, 'post_tag' => true, 'nav_menu' => true, 'link_category' => true, 'post_format' => true);
		return !isset($t[$tax]);
	}



	public static function needsPrefix($item, $type)
	{
		if($type == 'taxonomy'){
			return self::isSpecificCustomTax($item);
		}
		return self::isSpecificCpt($item);
	}



	/**
	 * Helper method for checking if given post type is some specific type
	 * @param  string  $type post type name
	 * @return boolean
	 */
	public static function isSpecificCpt($type)
	{
		$cpts = apply_filters('wplatte-cpts', array());

		if(!self::$already){
			self::$cptPrefixes = apply_filters('wplatte-cpt-prefixes', self::$cptPrefixes);
			self::$already = true;
		}

		$prefix = self::$cptPrefixes['post'];

		return isset($cpts["{$prefix}{$type}"]);
	}



	/**
	 * Helper method for checking if given taxonomy is custom
	 * @param  string  $tax taxonomy name
	 * @return boolean
	 */
	public static function isSpecificCustomTax($tax)
	{
		$taxs = apply_filters('wplatte-taxs', array());

		if(!self::$already){
			self::$cptPrefixes = apply_filters('wplatte-cpt-prefixes', self::$cptPrefixes);
			self::$already = true;
		}

		$prefix = self::$cptPrefixes['taxonomy'];

		return isset($taxs["{$prefix}{$tax}"]);
	}



	/**
	 * underscore_sepeated -> ClassName
	 * dash-sepeated -> ClassName
	 * @param  string $s
	 * @return string
	 */
	public static function camelize($s)
	{
		$s = ucwords(strtolower(str_replace(array('-', '_'), ' ', $s)));
		return str_replace(' ', '', $s);
	}

}
