<?php


class AitUtils
{

	public function __construct()
	{
		throw new LogicException(__CLASS__ . ' is a static class. Can not be instantiate.');
	}



	public static function isAjax()
	{
		return (defined('DOING_AJAX') and DOING_AJAX === true);
	}



	public static function isAitServer($server = '')
	{
		if(!$server)
			return defined('AIT_SERVER');
		else
			return (defined('AIT_SERVER') and AIT_SERVER == $server);
	}



	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @param  array   $array
	 * @param  string  $prepend
	 * @return array
	 */
	public static function arrayDot($array, $prepend = '')
	{
		$results = array();

		foreach($array as $key => $value){
			if(is_array($value)){
				$results = array_merge($results, self::arrayDot($value, $prepend . $key . '.'));
			}else{
				$results[$prepend . $key] = $value;
			}
		}

		return $results;
	}



	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function arrayDotGet($array, $key, $default = null)
	{
		if(is_null($key)) return $array;

		if(isset($array[$key])) return $array[$key];

		foreach(explode('.', $key) as $segment){
			if(!is_array($array) or !array_key_exists($segment, $array)){
				return $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}



	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function arrayDotSet(&$array, $key, $value)
	{
		if(is_null($key)) return $array = $value;

		$keys = explode('.', $key);

		while(count($keys) > 1){
			$key = array_shift($keys);

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if(!isset($array[$key]) or !is_array($array[$key])){
				$array[$key] = array();
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;

		return $array;
	}



	/**
	 * Starts the $haystack string with the prefix $needle?
	 * @param  string
	 * @param  string
	 * @return bool
	 */
	public static function startsWith($haystack, $needle)
	{
		return NStrings::startsWith($haystack, $needle);
	}



	/**
	 * Ends the $haystack string with the suffix $needle?
	 * @param  string
	 * @param  string
	 * @return bool
	 */
	public static function endsWith($haystack, $needle)
	{
		return NStrings::endsWith($haystack, $needle);
	}



	/**
	 * Does $haystack contain $needle?
	 * @param  string
	 * @param  string
	 * @return bool
	 */
	public static function contains($haystack, $needle)
	{
		return NStrings::contains($haystack, $needle);
	}



	/**
	 * Checks if given url is absolute url
	 * @param  string  $url Absolute URL to http resource
	 * @return boolean
	 */
	public static function isAbsUrl($url)
	{
		$url = trim($url);
		return (self::startsWith($url, 'http') or self::startsWith($url, '//'));
	}



	/**
	 * Checks if given url points to external resource.
	 * @param  string  $url Absolute URL to http resource
	 * @return boolean
	 */
	public static function isExtUrl($url)
	{
		$url = trim($url);
		$parts = parse_url($url);
		return ((self::startsWith($url, 'http') or self::startsWith($url, '//')) and !(isset($parts['host']) and self::contains(site_url(), $parts['host'])));
	}



	public static function addPrefix($item, $type = '', $prefix = "ait-")
	{
		if(empty($item)) return $item;

		if($type == 'taxonomy'){
			if(is_array($item)){
				foreach($item as $i => $tax){
					if(self::isAitCustomTax($tax)){
						$item[$i] = $prefix . $tax;
					}
				}
			}else{
				if(self::isAitCustomTax($item)){
					$item = $prefix . $item;
				}
			}

			return $item;
		}

		if($type == 'post'){
			if(self::isAitCpt($item)){
				$item = $prefix . $item;
			}

			return $item;
		}

		if(!self::startsWith($item, $prefix)){
			return $prefix . $item;
		}else{
			return $item;
		}
	}



	public static function stripPrefix($item, $prefix = "ait-")
	{
		$len = strlen($prefix);

		if(is_array($item)){
			foreach($item as $i => $string){
				if($len and self::startsWith($string, $prefix)){
					$item[$i] = substr($string, $len);
				}
			}
			return $item;
		}

		if($len and self::startsWith($item, $prefix)){
			return substr($item, $len);
		}else{
			return $item;
		}
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
	 * Helper method for checking if given post type is custom from AIT Toolkit plugin
	 * @param  string  $type post type name
	 * @return boolean
	 */
	public static function isAitCpt($type)
	{
		$aitCpts = get_post_types(array('ait-cpt' => true));
		return isset($aitCpts["ait-{$type}"]);
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



	/**
	 * Helper method for checking if given taxonomy is custom
	 * @param  string  $tax taxonomy name without "ait-" prefix
	 * @return boolean
	 */
	public static function isAitCustomTax($tax)
	{
		$aitTaxs = get_taxonomies(array('ait-tax' => true));
		return isset($aitTaxs["ait-{$tax}"]);
	}



	/**
	 * Converts to web safe characters [a-z0-9-] text.
	 * @param  string  UTF-8 encoding
	 * @param  string  allowed characters
	 * @param  bool
	 * @return string
	 */
	public static function webalize($s, $charlist = null, $lower = true)
	{
		return NStrings::webalize($s, $charlist, $lower);
	}



	/**
	 * Trim text with HTML tags
	 * @param string Text to be trimmed
	 * @param int Length of returned texts in characters
	 * @param string Custom character for the ellipsis (added by AIT)
	 * @return string Trimmed text with right HTML endings
	 * @copyright Jakub Vrana, http://php.vrana.cz/
	 */
	public static function trimHtmlContent($s, $limit, $ellipsis = null)
	{
		$length = 0;
		$ellipsis = isset($ellipsis) ? $ellipsis : ' [...]';
		$tags = array(); // not closed tags until now
		for ($i=0; $i < strlen($s) && $length < $limit; $i++){
			switch ($s[$i]) {
				case '<':
					// read tag
					$start = $i+1;
					while($i < strlen($s) && $s[$i] != '>' && !ctype_space($s[$i])){
						$i++;
					}
					$tag = substr($s, $start, $i - $start);
					// skip attributes
					$in_quote = '';
					while($i < strlen($s) && ($in_quote || $s[$i] != '>')){
						if(($s[$i] == '"' || $s[$i] == "'") && !$in_quote){
							$in_quote = $s[$i];
						}elseif ($in_quote == $s[$i]){
							$in_quote = '';
						}
						$i++;
					}
					if ($s[$start] == '/') { // closing tag
						array_shift($tags);
					} elseif ($s[$i-1] != '/') { // openning tag
						array_unshift($tags, $tag);
					}
					break;

				case '&':
					$length++;
					while ($i < strlen($s) && $s[$i] != ';') {
						$i++;
					}
					break;

				default:
					$length++;

					while ($i+1 < strlen($s) && ord($s[$i+1]) > 127 && ord($s[$i+1]) < 192) {
						$i++;
					}
			}
		}
		$s = substr($s, 0, $i);
		(strlen($s) > $limit) ? $s .= $ellipsis : '';
		if ($tags) {
			$s .= "</" . implode("></", $tags) . ">";
		}
		return $s;
	}



	/**
	 * Converts HEX color to RGB channels
	 * @param  string $hexColor Color in HEX format
	 * @return array           RGB channels
	 */
	public static function hex2rgb($hexColor)
	{
		if ($hexColor[0] == '#')
			$hexColor = substr($hexColor, 1);

		if (strlen($hexColor) == 6)
			list($r, $g, $b) = array($hexColor[0].$hexColor[1], $hexColor[2].$hexColor[3], $hexColor[4].$hexColor[5]);
		elseif (strlen($hexColor) == 3)
			list($r, $g, $b) = array($hexColor[0].$hexColor[0], $hexColor[1].$hexColor[1], $hexColor[2].$hexColor[2]);
		else
			return array('r' => 'you', 'g' => 'entered wrong', 'b' => "hex color: $hexColor");

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return array('r' => $r, 'g' => $g, 'b' => $b);
	}



	public static function rgba2hex($string)
	{
		$string = trim($string);

		if(self::startsWith($string, 'rgba')){

			$values = array_map('trim', explode(',', substr($string, 5, -1)));

			$a = array_pop($values);

			$out = "#";

			foreach ($values as $c)
			{
				$hex = base_convert($c, 10, 16);
				$out .= ($c < 16) ? ("0" . $hex) : $hex;
			}

			$return = (object) array(
				'hex' => $out,
				'opacity' => $a * 100,
				'a' => $a,
			);

			return $return;
		}else{
			return (object) array(
				'hex' => $string,
				'opacity' => 100,
				'a' => 1,
			);
		}
	}



	/**
	 * Creates classname from id, e.g. parallax-portfolio -> AitParallaxPortfolioElement
	 * @param  string $id     Id in with dashes
	 * @param  string $suffix Classname suffix, e.g. 'Element', 'OptionControl'
	 * @param  string $prefix Classname prefix, default 'Ait'
	 * @return string         Full classname
	 */
	public static function id2class($id,  $suffix, $prefix = 'Ait')
	{
		return $prefix . ucfirst(self::dash2camel($id)) . ucfirst($suffix);
	}



	/**
	 * Reverse operation of id2classname method
	 * @param  string $classname Classname suffix, e.g. 'Element', 'OptionControl'
	 * @param  string $suffix Classname suffix, e.g. 'Element', 'OptionControl'
	 * @param  string $prefix Classname prefix, default 'Ait'
	 * @return string         Id with dashes
	 */
	public static function class2id($classname,  $suffix, $prefix = 'Ait')
	{
		return self::camel2dash(substr($classname, strlen($prefix), -strlen($suffix)));
	}



	/**
	 * dash-separated -> camelCase.
	 * @param  string
	 * @return string
	 */
	public static function dash2camel($s)
	{
		$s = self::_2class($s);
		$s[0] = strtolower($s[0]);
		return $s;
	}



	/**
	 * camelCaseAction name -> dash-separated.
	 * @param  string
	 * @return string
	 */
	public static function camel2dash($s)
	{
		$s = preg_replace('#(.)(?=[A-Z])#', '$1-', $s);
		$s = strtolower($s);
		return $s;
	}



	/**
	 * dash-sepeated -> ClassName
	 * @param  string $s
	 * @return string
	 */
	public static function dash2class($s)
	{
		return self::_2class($s);
	}



	/**
	 * underscore_sepeated -> ClassName
	 * @param  string $s
	 * @return string
	 */
	public static function _2class($s)
	{
		$s = ucwords(strtolower(str_replace(array('-', '_'), ' ', $s)));
		return str_replace(' ', '', $s);
	}



	/**
	 * Deletes a file or directory.
	 */
	public static function delete($fromDir, $mask = '*', $dirItself = true)
	{
		if(is_dir($fromDir)){
			foreach(NFinder::find($mask)->from($fromDir)->childFirst() as $item){
				if($item->isDir()){
					@rmdir($item);
				}else{
					@unlink($item);
				}
			}
			if($dirItself){
				@rmdir($fromDir);
			}

		}elseif(is_file($fromDir)){
			@unlink($fromDir);
		}
	}



	/**
	 * Mkdir
	 */
	public static function mkdir($dir)
	{
		if(file_exists($dir)) return $dir;

		$d = wp_mkdir_p($dir);

		return $d ? $dir : false;
	}



	/**
	 * Unified creating of nonces with ait- prefix
	 *
	 * @param  string|int $action  Scalar value to add context to the nonce. Default: -1
	 * @param  bool       $raw     Whether $action is raw - will not be prefixed
	 * @return string              The one use form token
	 */
	public static function nonce($action = -1, $raw = false)
	{
		$prefix = !$raw ? 'ait-' : '';
		return wp_create_nonce("{$prefix}{$action}");
	}



	/**
	 * Unified checking of ajax nonces iwth ait- prefix
	 * In nonce is verified then true is returned otherwise
	 * is sent json error message and scripts is exited with wp_die()
	 *
	 * @param  string|int $action  Scalar value to add context to the nonce. Default: -1
	 * @param  bool       $raw     Whether $action is raw - will not be prefixed
	 * @var bool|void
	 */
	public static function checkNonce($nonce, $action = -1, $raw = false)
	{
		$prefix = !$raw ? 'ait-' : '';

		return wp_verify_nonce($nonce, "{$prefix}{$action}");
	}



	/**
	 * Unified checking of ajax nonces iwth ait- prefix
	 * In nonce is verified then true is returned otherwise
	 * is sent json error message and scripts is exited with wp_die()
	 *
	 * @param  string|int $action  Scalar value to add context to the nonce. Default: -1
	 * @param  bool       $raw     Whether $action is raw - will not be prefixed
	 * @var bool|void
	 */
	public static function checkAjaxNonce($action = -1, $raw = false)
	{
		$prefix = !$raw ? 'ait-' : '';

		if(check_ajax_referer("{$prefix}{$action}", false, false))
			return true;
		else
			wp_send_json_error("Checking ajax nonce failed.");
	}



	/**
	 * Redirecting in AIT admin pages
	 * @param  array  $params Same as add_query_arg()
	 * @return void           Exits
	 */
	public static function adminRedirect($params = array())
	{
		$url = self::adminPageUrl($params);
		wp_redirect(esc_url_raw($url));
		exit;
	}



	/**
	 * Builds AIT admin page URL
	 * @param  array $params Same as add_query_arg()
	 * @return string        Page URL
	 */
	public static function adminPageUrl($params)
	{
		if(isset($params['page']))
			$params['page'] = "ait-" . $params['page'];

		return add_query_arg($params, admin_url("admin.php"));
	}



	/**
	 * Convert a PHP date format to a jQuery UI DatePicker format
	 *
	 * @param string $dateFormat a date format
	 * @return string
	 */
	public static function phpDate2jsDate($dateFormat)
	{
		$chars = array(
			// Day
			'd' => 'dd', 'j' => 'd', 'l' => 'DD', 'D' => 'D',
			// Month
			'm' => 'mm', 'n' => 'm', 'F' => 'MM', 'M' => 'M',
			// Year
			'Y' => 'yy', 'y' => 'y',
		);
		return strtr((string)$dateFormat, $chars);
	}


	/**
	 * Convert a PHP date format to a jQuery UI DatePicker format
	 *
	 * @param string $dateFormat a date format
	 * @return string
	 */
	public static function phpTime2jsTime($timeFormat)
	{
		$chars = array(
			// am, pm suffix
			'a' => 'tt', 'A' => 'TT',
			// Hours
			'g' => 'h', 'G' => 'H', 'h' => 'hh', 'H' => 'HH',
			// Minutes
			'i' => 'mm',
			// Seconds
			's' => 'ss',
		);
		return strtr((string)$timeFormat, $chars);
	}



	/**
	 * Convert a PHP date format to a jQuery UI DatePicker format
	 *
	 * @param string $dateFormat a date format
	 * @return string
	 */
	public static function jsDate2phpDate($dateFormat)
	{
		$chars = array(
			// Day
			'dd' => 'd', 'd' => 'j', 'DD' => 'l', 'D' => 'D',
			// Month
			'mm' => 'm', 'm' => 'n', 'MM' => 'F', 'M' => 'M',
			// Year
			'yy' => 'Y', 'y' => 'y',
		);
		return strtr((string)$dateFormat, $chars);
	}



	public static function jsTime2phpTime($timeFormat)
	{
		$chars = array(
			// am, pm suffix
			'tt' => 'a', 'TT' => 'A',
			// Hours
			'h' => 'g', 'H' => 'G', 'hh' => 'h', 'HH' => 'H',
			// Minutes
			'mm' => 'i',
			// Seconds
			'ss' => 's',
		);
		return strtr((string)$timeFormat, $chars);
	}

}
