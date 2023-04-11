<?php


class AitWpAdminExtensions
{


	public static function register()
	{
		$ext = self::loadConfig();

		if(!AitUtils::isAjax()){ // these are no needed during ajax request

			if($ext->wpAdmin->adminFooterText){
				add_filter('admin_footer_text', array(__CLASS__, 'adminFooterText'));
			}
		}
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



	public static function adminFooterText($text)
	{
		$t = aitOptions()->getOptionsByType('theme');
		if(isset($t['adminBranding']['adminFooterText'])){
			return '<span id="footer-thankyou">' . AitLangs::getCurrentLocaleText($t['adminBranding']['adminFooterText']) . '</span>';
		}else{
			return $text;
		}
	}


}
