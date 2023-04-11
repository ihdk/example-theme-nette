<?php


/**
 * Static wrapper for AIT Languages plugin functionality
 */
class AitLangs
{

	protected static $defaultLang;



	protected static function _defaultLang()
	{
		if(!self::$defaultLang){
			self::$defaultLang = new AitDefaultLanguage();
		}
		return self::$defaultLang;
	}



	/**
	 * Whether AIT Languages Plugin is enabled
	 * @return boolean
	 */
	public static function isEnabled()
	{
		global $polylang;
		$filtered = apply_filters('ait-langs-enabled', aitIsPluginActive('languages'));

		if($filtered === true and !aitIsPluginActive('languages')){
			return false;
		}else{
			return $filtered;
		}
	}



	/**
	 * Gets default locale of default language
	 * @return string
	 */
	public static function getDefaultLocale()
	{
		if(!self::isEnabled()) return get_locale();
		$locale = '';

		if(function_exists('aitLangsGetDefaultLanguage')){
			$locale = aitLangsGetDefaultLanguage('locale');
		}elseif(function_exists('pll_default_language')){ // since Polylang 1.8, it includes api.php only when there is at least one language
			$locale = pll_default_language('locale');
		}

		if(!$locale){
			return get_locale();
		}

		return $locale;
	}



	/**
	 * Gets current locale of current language
	 * @return string
	 */
	public static function getCurrentLocale()
	{
		return get_locale();
	}


	/**
	 * Gets language code (slug) of current language
	 * @return string
	 */
	public static function getCurrentLanguageCode()
	{
		$locale = get_locale();
		if($locale == 'zh_CN'){
			return 'cn';
		}elseif($locale == 'zh_TW'){
			return 'tw';
		}elseif($locale == 'pt_BR'){
			return 'br';
		}else{
			$lang = self::getCurrentLang();
			if($lang){
				return $lang->slug;
			}
			return substr($locale, 0, 2);
		}
	}



	public static function getLocalesList()
	{
		$langs = self::getLanguagesList();
		$locales = array();

		foreach($langs as $lang){
			$locales[] = $lang->locale;
		}

		return $locales;
	}



	public static function getDefaultLang()
	{
		if(!self::isEnabled()) return self::_defaultLang();

		$lang = false;

		if(function_exists('aitLangsGetDefaultLanguage')){
			$lang = aitLangsGetDefaultLanguage();
		}else{
			global $polylang;
			if(isset($polylang->options['default_lang']) && ($default_lang = $polylang->model->get_language($polylang->options['default_lang']))){
				$lang = $default_lang;
			}
		}

		if(!$lang){
			$lang = self::_defaultLang();
		}

		return $lang;
	}



	public static function getCurrentLang()
	{
		if(!self::isEnabled()) return self::_defaultLang();

		$lang = null;

		if(function_exists('aitLangsGetCurrentLanguage')){
			$lang = aitLangsGetCurrentLanguage();
		}else{
			global $polylang;
			if(isset($polylang->curlang) and $polylang->curlang){
				$lang = $polylang->curlang;
			}else{
				self::getDefaultLang();
			}
		}

		return $lang ? $lang : self::getDefaultLang();
	}



	public static function isUsedNowDefaultLocale()
	{
		return (self::getCurrentLocale() == self::getDefaultLocale());
	}



	public static function isThisDefaultLocale($locale)
	{
		return (self::getDefaultLocale() == $locale);
	}



	public static function getLanguagesList()
	{
		global $polylang;

		if(self::isEnabled() and $polylang and ($list = $polylang->model->get_languages_list())){
			return $list;
		}

		return array(self::_defaultLang());
	}



	public static function getSwitcherLanguages()
	{
		global $polylang;
		if(!self::isEnabled() or !$polylang) return array();

		if(function_exists('aitLangsGetCurrentLanguage')){ // old ait-languages <1.7.x
			return pll_the_languages(array('raw' => true, 'new_structure' => true));
		}else{
			$langs = pll_the_languages(array('raw' => true, 'show_flags' => true));
			$langsObj = array();
			if(!empty($langs)){
				foreach($langs as $lang){
					$langsObj[] = (object) array(
						'id'             => $lang['id'],
						'slug'           => $lang['slug'],
						'name'           => $lang['name'],
						'url'            => $lang['url'],
						'flag'           => $lang['flag'],
						'flagUrl'        => '',
						'isCurrent'      => $lang['current_lang'],
						'hasTranslation' => !$lang['no_translation'],
						'htmlClass'      => implode(' ', $lang['classes']),
					);
				}
			}
			return $langsObj;
		}
	}



	public static function isFilteredOut($lang)
	{
		if(!self::isEnabled()) return false;

		$post = get_post();

		static $_blog;
		if(!$_blog){
			$blogPageId = get_option('page_for_posts');
			if($blogPageId){
				$_blog = get_post($blogPageId);
			}
		}

		$slug = '';

		if($post and $_blog and $post->ID == $_blog->ID){
			$slug = self::_getLangForFiltering();
		}elseif($post and $post->post_status != 'auto-draft'){
			$slug = self::getPostLang($post->ID)->slug;
		}elseif($post and $post->post_status == 'auto-draft'){
			$slug = self::getDefaultLang()->slug;
		}else{
			$slug = self::_getLangForFiltering();
		}

		return ($slug and $lang->slug != $slug);
	}



	protected static function _getLangForFiltering()
	{
		if(function_exists('aitLangsGetLangForFiltering')){
			$slug = aitLangsGetLangForFiltering();
		}else{
			$slug = get_user_meta(get_current_user_id(), 'pll_filter_content', true);
		}

		return $slug;
	}



	/**
	 * Returns lang code for filtering content in admin
	 * @return string Lang code as 'en', 'sk' or empty string for all langauges
	 */
	public static function getFilteringLangCode()
	{
		return aitIsPluginActive('languages') ? self::_getLangForFiltering() : '';
	}



	/**
	 * Gets string for current locale
	 * @param  array|object|string $localesAndTexts Associative array of locales and texts, e.g. array('en_US' => 'Some text')
	 * @param  string              $defaultText     Default text when translated string does not exist
	 * @return string                               Text for current locale
	 */
	public static function getCurrentLocaleText($localesAndTexts, $defaultText = '')
	{
		$currentLocale = self::getCurrentLocale();
		if(is_admin()){
			$post = self::checkIfPostAndGetLang();
			if($post){
				$currentLocale = $post->locale;
			}
		}

		$return = $defaultText;

		if(is_array($localesAndTexts) and isset($localesAndTexts[$currentLocale])){
			$return = $localesAndTexts[$currentLocale];
		}elseif(is_array($localesAndTexts) and isset($localesAndTexts['en_US'])){
			$return = $localesAndTexts['en_US'];
		}elseif(is_object($localesAndTexts) and isset($localesAndTexts->{$currentLocale})){
			$return = $localesAndTexts->{$currentLocale};
		}elseif(is_object($localesAndTexts) and isset($localesAndTexts->{'en_US'})){
			$return = $localesAndTexts->{'en_US'};
		}elseif(is_string($localesAndTexts) and !empty($localesAndTexts)){
			$return = $localesAndTexts;
		}

		return $return;
	}



	/**
	 * Gets string for default locale
	 * @param  array|object|string $localesAndTexts Associative array of locales and texts, e.g. array('en_US' => 'Some text')
	 * @param  string              $defaultText     Default text when translated string does not exist
	 * @return string                               Text for default locale
	 */
	public static function getDefaultLocaleText($localesAndTexts, $defaultText = '')
	{
		$defaultLocale = self::getDefaultLocale();

		if(is_array($localesAndTexts) and isset($localesAndTexts[$defaultLocale])){
			return $localesAndTexts[$defaultLocale];
		}elseif(is_array($localesAndTexts) and isset($localesAndTexts['en_US'])){
			return $localesAndTexts['en_US'];
		}elseif(is_object($localesAndTexts) and isset($localesAndTexts->{$defaultLocale})){
			return $localesAndTexts->{$defaultLocale};
		}elseif(is_object($localesAndTexts) and isset($localesAndTexts->{'en_US'})){
			return $localesAndTexts->{'en_US'};
		}elseif(is_string($localesAndTexts) and !empty($localesAndTexts)){
			return $localesAndTexts;
		}else{
			return $defaultText;
		}
	}



	public static function getPostLang($postId)
	{
		if(!self::isEnabled()) return self::_defaultLang();

		if(function_exists('PLL')){
			if($lang = PLL()->model->post->get_language($postId)){
				return $lang;
			}
		}else{
			global $polylang;

			if(isset($polylang) and ($lang = $polylang->model->get_post_language($postId))){
				return $lang;
			}
		}

		return self::_defaultLang();
	}



	public static function checkIfPostAndGetLang()
	{
		global $post;

		if($post){
			return self::getPostLang($post->ID);
		}

		return false;
	}



	public static function htmlClass($locale = '')
	{
		$class = array();
		$class[] = self::isEnabled() ? ' ait-langs-enabled ' : '';
		$class[] = $locale ? 'ait-lang-' . $locale : '';
		$class[] = ($locale and $locale == self::getDefaultLocale()) ? 'ait-lang-default' : '';

		$class = apply_filters('ait-langs-html-class', $class);
		return implode(' ', $class);
	}




	public static function getGmapsLang()
	{
		// https://developers.google.com/maps/faq#languagesupport
		// lang codes map:
		// WP locale => gmaps lang code
		$map = array(
			'bg_BG' => 'bg',
			'cs_CZ' => 'cs',
			'de_DE' => 'de',
			'de_CH' => 'de',
			'el'    => 'el',
			'en_US' => 'en',
			'en_CA' => 'en-ca',
			'en_GB' => 'en-gb',
			'es_ES' => 'es',
			'es_CL' => 'es-cl',
			'es_AR' => 'es-ar',
			'es_CL' => 'es-cl',
			'es_CO' => 'es-co',
			'es_GT' => 'es-gt',
			'es_MX' => 'es-mx',
			'es_PE' => 'es-pe',
			'es_PR' => 'es-pr',
			'es_VE' => 'es-ve',
			'fi'    => 'fi',
			'fr_FR' => 'fr',
			'fr_BE' => 'fr',
			'fr_CA' => 'fr',
			'hi_IN' => 'hi',
			'hr'    => 'hr',
			'hu_HU' => 'hu',
			'id_ID' => 'id',
			'it_IT' => 'it',
			'nl_NL' => 'nl',
			'pl_PL' => 'pl',
			'pt_BR' => 'pt-br',
			'pt_PT' => 'pt-pt',
			'ru_RU' => 'ru',
			'sk_SK' => 'sk',
			'sq'    => 'sq', // it seems gmaps does not support this lang
			'sv_SE' => 'sv',
			'tr_TR' => 'tr',
			'uk'    => 'uk',
			'zh_CN' => 'zh-cn',
			'zh_TW' => 'zh-tw',
		);

		$currentLocale = self::getCurrentLocale();

		if(isset($map[$currentLocale])){
			return $map[$currentLocale];
		}

		return 'en';
	}



	public static function getFullCalendarLocale()
	{
		$lang = self::getGmapsLang();

		return str_replace(
			array('pt-pt'),
			array('pt'),
			$lang
		);
	}
}
