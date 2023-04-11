<?php


/**
 * Manipulating with google fonts
 */
class AitGoogleFonts
{

	protected static $fontsList = array();



	protected static function loadFontsFromJson()
	{
		if(empty(self::$fontsList)){

			$content = @file_get_contents(aitPaths()->dir->fwConfig . '/google-fonts.json');
			if(!$content){
				return array();
			}

			self::$fontsList = json_decode($content);

		}

		return self::$fontsList->items;
	}



	public static function getAll()
	{
		return self::loadFontsFromJson();
	}



	public static function getByFontFamily($family)
	{

		$fonts = self::loadFontsFromJson();

		foreach($fonts as $font){
			if($font->family == $family){
				return $font;
			}
		}

		return false;

	}

}
