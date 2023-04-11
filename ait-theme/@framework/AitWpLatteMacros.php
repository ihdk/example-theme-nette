<?php


/**
 * Additional WpLatte macros specific for AIT Themes
 */
class AitWpLatteMacros extends NMacroSet
{

	public static $config;



	public static function install(NLatteCompiler $compiler, $config = null)
	{
		$me = new self($compiler);

		self::$config = $config;

		$me->addMacro('imageUrl', array($me, 'macroResizedImgUrl'));
		$me->addMacro('includeElement', array($me, 'macroIncludeElement'));
		$me->addMacro('dataAttr', array($me, 'macroDataAttr'));
		$me->addMacro('sidebar', 'dynamic_sidebar(%node.args);');
		$me->addMacro('googleAnalytics',  'echo ' . __CLASS__ . '::googleAnalytics(%node.args);');
		$me->addMacro('currency', 'echo ' . __CLASS__ . '::currency(%node.args)');
		$me->addMacro('videoEmbedUrl', 'echo ' . __CLASS__ . '::makeVideoEmbedUrl(%node.args);');
		$me->addMacro('videoThumbnailUrl', 'echo ' . __CLASS__ . '::makeVideoThumbnailUrl(%node.args);');
	}



	/**
	 * {includeElement $element}
	 * $element is instance of AitElement class
	 */
	public function macroIncludeElement(NMacroNode $node, NPhpWriter $writer)
	{
		$el = $writer->formatArgs();

		$params = array(
			"'el' => $el",
			"'element' => {$el}",
			"'htmlId' => {$el}->getHtmlId()",
			"'htmlClass' => {$el}->getHtmlClass()",
		);

		$paramsStr = implode(', ', $params);

		$include = $writer->write('NCoreMacros::includeTemplate(' . $el . '->getTemplate(), array(' . $paramsStr . ') + $template->getParameters(), $_l->templates[%var])',
			$this->getCompiler()->getTemplateId());

		if ($node->modifiers) {
			$includeElement = $writer->write('echo %modify(%raw->__toString(TRUE))', $include);
		} else {
			$includeElement = $include . '->render()';
		}

		$c = __CLASS__;

		return
			"if({$el}->getId() === 'columns' or {$el}->isEnabled()){
				{$includeElement};
			}else{
				echo {$c}::elementPlaceholder({$el});
			}";
	}



	public static function elementPlaceholder($element)
	{
		$elementColor = $element->getColor();
		$maxWidth = isset(aitOptions()->get('theme')->general->websiteWidth) ? aitOptions()->get('theme')->general->websiteWidth : '';
		$maxWidthStyle = is_numeric($maxWidth) && $maxWidth > 500 ? "style='max-width: {$maxWidth}px'" : "";

		return "
			<div class='ait-elm-placeholder-wrapper' {$maxWidthStyle}>
				<div class='ait-elm-placeholder'>
					<div class='ait-elm-placeholder-icon'>
						<i class='fa {$element->getIcon()}' style='color: {$elementColor}'></i>
					</div>

					<div class='ait-elm-placeholder-content'>
						<div class='ait-elm-placeholder-text'>
							<h2 style='color: {$elementColor}'>" . sprintf(__('%s Element', 'ait'), $element->title) . "</h2>" .
							/* translators: 1: The element title, 2: Name of a plugin */
							"<div>". sprintf(__('%1$s is available in %2$s plugin', 'ait'), '', 'AIT Elements Toolkit') ."</div>
						</div>
						<a href='https://www.ait-themes.club/wordpress-plugins/ait-elements-toolkit/?utm_source=wp-admin&utm_medium=wp-admin-banner&utm_campaign=Free-Theme' class='ait-elm-placeholder-button' style='color: {$elementColor}; background: {$elementColor}'>
							<i class='fa fa-download'></i>
							<span>". __("Download Plugin", 'ait') ."</span>
						</a>
					</div>
				</div>
			</div>
		";
	}



	public function macroResizedImgUrl(NMacroNode $node, NPhpWriter $writer)
	{
		$url = $node->tokenizer->fetchWord();
		$args = $writer->formatArray();

		if(!AitUtils::contains($args, '=>'))
			$args = substr($args, 6, -1);

		return $writer->write("echo aitResizeImage($url, $args)");
	}



	public function macroDataAttr(NMacroNode $node, NPhpWriter $writer)
	{
		$name = $node->tokenizer->fetchWord();
		$params = $writer->formatArray();

		return "echo aitDataAttr('$name', $params)";
	}



	public static function googleAnalytics($uaCode = '', $anonymizeIp = false)
	{
		if($uaCode){
			$aip = apply_filters('ait-ga-anonymize-ip', $anonymizeIp) ? "ga('set', 'anonymizeIp', true);" : '';
			return "
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', '{$uaCode}', 'auto');{$aip}ga('send', 'pageview');
</script>";
		}

		return '';
	}



	public static function currency($price = 0, $currency = 'USD')
	{
		$dollar   = '&#36;';
		$krone    = '&#107;&#114;';

		$currencies = array(
			'AUD' => array('symbol' => $dollar,                       ),
			'BRL' => array('symbol' => '&#82;'.$dollar,               ),
			'CAD' => array('symbol' => $dollar,                       ),
			'CZK' => array('symbol' => '&#75;&#269;',                 'position' => 'right'),
			'DKK' => array('symbol' => $krone,                        ),
			'EUR' => array('symbol' => '&nbsp;&#8364;',               'position' => 'right'),
			'HKD' => array('symbol' => $dollar,                       ),
			'HUF' => array('symbol' => '&#70;&#116;',                 ),
			'ILS' => array('symbol' => '&#8362;',                     ),
			'JPY' => array('symbol' => '&#165;',                      ),
			'MYR' => array('symbol' => '&#82;&#77;',                  ),
			'MXN' => array('symbol' => $dollar,                       ),
			'NOK' => array('symbol' => $krone,                        ),
			'NZD' => array('symbol' => $dollar,                       ),
			'PHP' => array('symbol' => '&#8369;',                     ),
			'PLN' => array('symbol' => '&#122;&#322;',                ),
			'GBP' => array('symbol' => '&#163;',                      ),
			'RUB' => array('symbol' => '&nbsp;&#1088;&#1091;&#1073;', 'position'  => 'right'),
			'SGD' => array('symbol' => $dollar,                       ),
			'SEK' => array('symbol' => $krone,                        ),
			'CHF' => array('symbol' => '&#67;&#72;&#70;',             ),
			'TWD' => array('symbol' => '&#78;&#84;'.$dollar,          ),
			'THB' => array('symbol' => '&#3647;',                     ),
			'TRY' => array('symbol' => '&#8378;',                     ),
			'USD' => array('symbol' => $dollar,                       ),
		);

		$priceLayout = "<span class='price' data-raw='%d'>%s</span>";
		$currencyLayout = "<span class='currency'>%s</span>";

		$formattedPrice = number_format_i18n($price, 2);

		if(!isset($currencies[$currency])){
			trigger_error("Currency $currency is not supported");
			$return = sprintf($currencyLayout, $dollar) . sprintf($priceLayout, $price, $formattedPrice);
		}

		$c = $currencies[$currency];

		if(isset($c['position']) and $c['position'] == 'right'){
			$return = sprintf($priceLayout, $price, $formattedPrice) . sprintf($currencyLayout, $c['symbol']) ;
		}else{
			$return = sprintf($currencyLayout, $c['symbol']) . sprintf($priceLayout, $price, $formattedPrice);
		}

		return apply_filters('wplatte-currency', $return, $price, $currency);
	}



	public static function makeVideoEmbedUrl($videoUrl)
	{
		$url = '#';
		$videoId = aitExtractVideoIdFromVideoUrl($videoUrl);

		if(AitUtils::contains($videoUrl, 'youtube')){
			$url = "https://www.youtube.com/embed/{$videoId}?wmode=opaque&amp;showinfo=0&amp;enablejsapi=1";
		}elseif(AitUtils::contains($videoUrl, 'vimeo')){
			$url = "https://player.vimeo.com/video/{$videoId}?title=0&amp;byline=0&amp;portrait=0";
		}

		return $url;
	}



	public static function makeVideoThumbnailUrl($videoUrl)
	{
		$url = '#';
		$videoId = aitExtractVideoIdFromVideoUrl($videoUrl);

		if(AitUtils::contains($videoUrl, 'youtube')){
			$url = "https://img.youtube.com/vi/{$videoId}/1.jpg";
		}elseif(AitUtils::contains($videoUrl, 'vimeo')){
			$clipData = @json_decode(@file_get_contents("http://vimeo.com/api/v2/video/{$videoId}.json"));
			if($clipData !== false){
				$url = $clipData[0]->thumbnail_small;
			}
		}

		return $url;
	}

}
