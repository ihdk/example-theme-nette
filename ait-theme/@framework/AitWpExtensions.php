<?php


class AitWpExtensions
{


	public static function register()
	{
		$ext = self::loadConfig();

		if(!AitUtils::isAjax()){ // these are no needed during ajax request

			// Remove rel attribute from the category list
			add_filter('wp_list_categories', array(__CLASS__, 'removeCategoryRelAtt'));
			add_filter('the_category', array(__CLASS__, 'removeCategoryRelAtt'));

			if(AIT_THEME_PACKAGE === 'basic'){
				add_filter('ait-templates-options', function($options){
					if(isset($options['theme']['footer']['text'])){
						$text = sprintf(base64_decode('UG93ZXJlZCBieSAlcyBXb3JkUHJlc3MgdGhlbWUgZnJvbSA8YSBocmVmPSJodHRwczovL3d3dy5haXQtdGhlbWVzLmNsdWIvIj5BaXRUaGVtZXMuY2x1YjwvYT4='), wp_get_theme()->name);
						if(is_array($options['theme']['footer']['text'])){
							foreach($options['theme']['footer']['text'] as $locale => $value){
								$options['theme']['footer']['text'][$locale] = $text;
							}
						}else{
							$options['theme']['footer']['text'] = $text;
						}
						return $options;
					}
					return $options;
				});

				add_filter('ait-get-full-config', function($config, $type){
					if($type === 'theme'){
						foreach($config['footer']['@options'] as $i => $v){
							if(isset($v['text'])){
								unset($config['footer']['@options'][$i]['text']);
								return $config;
							}
						}
						return $config;
					}
					return $config;
				}, 10, 2);
			}

			if($ext->wp->loginPageBranding){
				global $pagenow;

				if($pagenow == 'wp-login.php'){
					add_action('login_head', array(__CLASS__, 'loginPageBranding'));
					add_filter('login_headerurl', array(__CLASS__, 'loginPageBranding'));
					add_filter('login_headertext', array(__CLASS__, 'loginPageBranding'));
				}
			}

			add_filter('http_request_args', array(__CLASS__, 'excludeActiveAitThemeFromWpOrgUpdateCheck'), 10, 2);
		}

		// Text Widget can have shortcodes too
		add_filter('widget_text', 'do_shortcode');

		/* https://srd.wordpress.org/plugins/allow-cyrillic-usernames/ */
		add_filter('sanitize_user', array(__CLASS__, 'allowCyrillicUsernames'), 10, 3);
	}



	public static function loadConfig()
	{
		$extensionsFilePath = aitPath('config', '/wp-extensions.php');

		if($extensionsFilePath === false){
			$ext = require aitPaths()->dir->fwConfig . '/wp-extensions.php';
		}else{
			$ext = include $extensionsFilePath;
		}
		return $ext;
	}



	public static function removeCategoryRelAtt($output)
	{
  		return str_replace(' rel="category tag"', '', $output);
	}



	public static function loginPageBranding($input = '')
	{
		$t = aitOptions()->getOptionsByType('theme');
		$branding = $t['adminBranding'];

		if(current_filter() == 'login_head'){
			if(isset($branding["loginScreenCss"]) and $branding["loginScreenCss"]){
				echo "<style>" . $branding["loginScreenCss"] . "</style>\n";
			}

			if(isset($branding["loginScreenLogo"]) and $branding["loginScreenLogo"]){
				$css = '.login h1 a {background-image: url("%s"); background-size: 274px 63px; width: 274px}';
				echo "<style>" . sprintf($css, $branding["loginScreenLogo"]) . "</style>\n";
			}
			return; // this is action, not filter
		}


		if(current_filter() == 'login_headerurl' and isset($branding["loginScreenLogoLink"]) and $branding["loginScreenLogoLink"]){
			return $branding["loginScreenLogoLink"];
		}


		if(current_filter() == 'login_headertext' and isset($branding["loginScreenLogoTooltip"]) and $branding["loginScreenLogoTooltip"]){
			return AitLangs::getCurrentLocaleText($branding["loginScreenLogoTooltip"], $input);
		}

		return $input;
	}



	/**
	 * Allow cyrilic characters for usernames
	 * https://srd.wordpress.org/plugins/allow-cyrillic-usernames/
	 */
	public static function allowCyrillicUsernames($username, $raw_username, $strict)
	{
		$username = wp_strip_all_tags( $raw_username );
		$username = remove_accents( $username );
		// Kill octets
		$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );
		$username = preg_replace( '/&.+?;/', '', $username ); // Kill entities

		// If strict, reduce to ASCII and Cyrillic characters for max portability.
		if ( $strict )
			$username = preg_replace( '|[^a-zа-я0-9 _.\-@]|iu', '', $username );

		$username = trim( $username );
		// Consolidate contiguous whitespace
		$username = preg_replace( '|\s+|', ' ', $username );

		return $username;
	}



	public static function excludeActiveAitThemeFromWpOrgUpdateCheck($args, $url)
	{
		if($url === 'https://api.wordpress.org/themes/update-check/1.1/'){

			$body = json_decode($args['body']['themes']);
			$themes = (array) $body->themes;

			if(isset($themes[AIT_THEME_CODENAME])){
				unset($themes[AIT_THEME_CODENAME]);
			}

			$body->themes = $themes;
			$args['body']['themes'] = wp_json_encode($body);

			return $args;
		}

		return $args;
	}
}
