<?php


/**
 * Additionals Latte helpers specific for WpLatte and WordPress environment
 */
class WpLatteTemplateHelpers
{

	private static $helpers = array(
		'printf'    => 'sprintf',
		'shortcode' => 'do_shortcode',
	);

	private static $originalDateFormat;



	/**
	 * Try to load the requested helper.
	 * @param  string  helper name
	 * @return callable
	 */
	public static function loader($helper)
	{
		self::$originalDateFormat = get_option('date_format');

		if(method_exists(__CLASS__, $helper)){
			return callback(__CLASS__, $helper);
		}elseif(isset(self::$helpers[$helper])){
			return self::$helpers[$helper];
		}
	}



	public static function striptags($string, $allowedTags = '')
	{
		$string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
		$string = strip_tags($string);
		return trim($string);
	}



	public static function stripAllTags($string)
	{
		$string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
		$string = strip_tags($string);
		return trim($string);
	}



	public static function stripTagsExcept($string, $allowedTags = '')
	{
		$string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
		$string = strip_tags($string, $allowedTags);
		return trim($string);
	}



	/**
	 * Truncates string to given number of words
	 * It uses code from wp_trim_words
	 *
	 * @param  string  $string
	 * @param  int     $numberOfWords
	 * @param  string  $more          More text, default &hellip;
	 * @return string
	 */
	public static function trimWords($string, $numberOfWords = 55, $more = null)
	{
		if($more === null)
			$more = __('&hellip;', 'wplatte');

		$originalString = $string;

		/* translators: If your word count is based on single characters (East Asian characters),
		   enter 'characters'. Otherwise, enter 'words'. Do not translate into your own language. */
		if(_x('words', 'word count: "words" or "characters"?', 'wplatte') == 'characters'  and preg_match( '/^utf\-?8$/i', get_option('blog_charset'))){
			$string = trim(preg_replace( "/[\n\r\t ]+/", ' ', $string), ' ');
			preg_match_all('/./u', $string, $wordsArray);
			$wordsArray = array_slice($wordsArray[0], 0, $numberOfWords + 1);
			$sep = '';
		}else{
			$wordsArray = preg_split("/[\n\r\t ]+/", $string, $numberOfWords + 1, PREG_SPLIT_NO_EMPTY);
			$sep = ' ';
		}

		if(count($wordsArray) > $numberOfWords){
			array_pop($wordsArray);
			$string = implode($sep, $wordsArray);
			$string = $string . $more;
		} else {
			$string = implode($sep, $wordsArray);
		}

		return apply_filters('wp_trim_words', $string, $numberOfWords, $more, $originalString );
	}



	/**
	  * Date/time formatting.
	  * @param  string|int|DateTime
	  * @param  string
	  * @return string
	  */
	 public static function date($rawMySqlDate, $format = '')
	 {
		if(empty($rawMySqlDate)){
			return '';
		}

		if(!$format){
			$format = self::$originalDateFormat;
		}

		return mysql2date($format, $rawMySqlDate, false); // not translated date
	 }



	/**
	 * Formats and returns localized date
	 * Uses mysql2date function
	 *
	 * @param  string  $string input date/timestamp
	 * @param  string  $format output date format
	 * @return string formatted localised date
	 */
	public static function dateI18n($rawMySqlDate, $format = '')
	{
		if(empty($rawMySqlDate)){
			return '';
		}

		if(!$format){
			$format = self::$originalDateFormat;
		}

		return mysql2date($format, $rawMySqlDate, true); // translated day
	}

}
