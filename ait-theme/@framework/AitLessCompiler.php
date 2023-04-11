<?php


/**
 * Compile LESS to CSS
 */
class AitLessCompiler
{

	protected static $less;

	protected $cacheDir;
	protected $cacheUrl;
	protected $lessVariables = array();



	public function __construct($cacheDir, $cacheUrl)
	{
		$this->cacheDir = $cacheDir;
		$this->cacheUrl = $cacheUrl;
	}



	public function compileFile($inputFile, $params)
	{
		$less = self::create();

		$inputFileBasename = basename($inputFile, '.less');

		$v = "-" . AIT_THEME_VERSION;
		$oid = isset($params['oid']) ? $params['oid'] : '';
		$lang = isset($params['lang']) ? "-{$params['lang']}" : '';


		$globalOptionsVariables = $this->getLessVariables();
		$variables = $this->getLessVariables($oid);

		if($globalOptionsVariables === $variables){
			$oid = ''; // do not generate page specific css file when that page does not have own page builder options set
		}


		$outputFile = "/{$inputFileBasename}{$v}{$oid}{$lang}.css";
		$cacheFile = $this->cacheDir . "/.ht-{$inputFileBasename}{$v}{$oid}{$lang}.less-cache";

		if(file_exists($cacheFile)){
			$cache = unserialize(file_get_contents('safe://' . $cacheFile));
		}else{
			$cache = $inputFile;
		}

		if($lang){
			$variables['current-lang'] = $params['lang'];
		}

		$less->setVariables($variables);

		$result = array(
			'inputFile' => $inputFile,
			'error'     => false,
			'errorMsg'  => '',
			'embedCss'  => '',
			'url'       => '',
			'version'   => '',
			'isEmpty'   => false,
		);

		try{
			$newCache = $less->cachedCompile($cache, AIT_DEV);

			if(empty($newCache['compiled'])){
				$result['isEmpty'] = true;
				return $result;
			}

			if(!is_array($cache) or $newCache["updated"] > $cache["updated"]){

				@file_put_contents('safe://' . $cacheFile, serialize($newCache));

				$css = '';
				if(AIT_DEV){
					$css ="/*\n";
					foreach($variables as $var => $value){
						$css .= "@{$var}: {$value}\n";
					}
					$css .="*/\n\n";
				}

				$written = @file_put_contents('safe://' . $this->cacheDir . $outputFile, $css . $newCache['compiled']);

				if($written === false){
					$result['error'] = true;
					$result['embedCss'] = $newCache['compiled'];
				}else{
					$result['url'] = $this->cacheUrl . $outputFile;
					$result['version'] = $newCache["updated"];
				}
			}else{
				$result['url'] = $this->cacheUrl . $outputFile;
				$result['version'] = is_array($cache) ? $cache['updated'] : $newCache["updated"];
			}

			return $result;

		}catch(Exception $e){
			$result['error'] = true;
			$result['embedCss'] = "\n\n/*  ==== LESS ERROR ==== */\nError in file '{$inputFile}'\n\n\n\n\n\n\n" . $e->getMessage() . "\n\n\n\n\n\n\n";
			$result['errorMsg'] = $e->getMessage();
			return $result;
		}
	}



	public function compileString($string)
	{
		$less = self::create();
		$less->setVariables($this->getLessVariables());

		$cacheFile = $this->cacheDir . sprintf("/custom-%s.css", md5($string));

		$result = array(
			'error'   => false,
			'isEmpty' => false,
			'css'     => '',
		);

		if(!is_file($cacheFile)){
			try{
				$css = $less->compile($string, 'custom-css');

				if(empty($css)){
					$result['isEmpty'] = true;
					return $result;
				}

				$result['css'] = $css;

				@file_put_contents('safe://' . $cacheFile, $css);

				return $result;

			}catch(Exception $e){
				$result['error'] = true;
				$result['css'] = "\n\n/*  ==== LESS ERROR ==== */\n\n\n\n\n\n\n" . $e->getMessage() . "\n\n\n\n\n\n\n";
				return $result;
			}
		}else{
			$result['css'] = file_get_contents('safe://' . $cacheFile);
			return $result;
		}
	}



	/**
	 * Factory for LESS parser
	 * @return AitLess
	 */
	public static function create($importDir = array(), $importUrl = array())
	{
		$hash = md5(implode('', $importDir) . implode('', $importUrl));

		if(!isset(self::$less[$hash])){
			$less = new AitLess;

			$less->importDir = !$importDir ? aitGetPaths('css', '', 'path') : $importDir;
			$less->importUrl = !$importUrl ? aitGetPaths('css', '', 'url') : $importUrl;

			$less->registerFunction('design-url', array(__CLASS__, 'lessFnDesignUrl'));
			$less->registerFunction('img-url', array(__CLASS__, 'lessFnImgUrl'));
			$less->registerFunction('fonts-url', array(__CLASS__, 'lessFnFontsUrl'));
			$less->registerFunction('assets-url', array(__CLASS__, 'lessFnAssetsUrl'));

			$less->setPreserveComments(false);

			if(!AIT_DEV){
				$formatter = new AitLessFormatterCompressed;

				$less->setPreserveComments(false);
				$less->setFormatter($formatter);
			}

			do_action('ait-create-less-compiler', $less);

			self::$less[$hash] = $less;
		}

		return self::$less[$hash];
	}



	/**
	 * Extracts css code which will be used in local options
	 * @param  string $content Raw content of style.less file
	 * @return array          Splitted content
	 */
	protected function preprocessStyle($content)
	{
		// without wrapping marks /*ait.layout.begin*/ ... /*ait.layout.end*/
		preg_match_all('#(?<=/\\*ait\.local\.begin\\*/).*?(?=/\\*ait\.local\.end\\*/)\\s*#s', $content, $matches);
		// with wrapping marks
		preg_match_all('#/\\*ait\.local\.begin\\*/.*?/\\*ait\.local\.end\\*/\\s*#s', $content, $matches2);

		if(isset($matches[0])){
			$content = str_replace($matches2[0], '', $content);
			return array(
				'main' => $content,
				'local' => implode("\n", $matches[0]),
			);
		}
	}



	/**
	 * Converts structured config array to simple key => value array
	 * @param  array $config  Config
	 * @param  array $options Options
	 * @return array          LESS variables
	 */
	public function getLessVariables($oid = '')
	{
		if(!isset($this->lessVariables[$oid])){
			$this->lessVariables[$oid] = $this->extractLessVariables($oid);
		}

		return $this->lessVariables[$oid];
	}



	protected function extractLessVariables($oid = '')
	{
		$fullConfig = aitConfig()->getFullConfig();

		if(isset($fullConfig['theme']['administrator']) or isset($fullConfig['theme']['adminBranding']) or isset($fullConfig['elements'])){
			unset($fullConfig['theme']['administrator'], $fullConfig['theme']['adminBranding'], $fullConfig['elements']);
		}

		if($oid){
			$options = aitOptions()->getLocalOptions($oid);
		}else{
			$options = aitOptions()->getGlobalOptions();
		}

		$defaultOptions = aitConfig()->getDefaults();

		$variables = $this->getDefaultLessVars();

		/** @var AitOptionsControlsGroupFactory $optionsControlsGroupFactory */
		$optionsControlsGroupFactory = AitTheme::getFactory('options-controls-group');

		foreach($fullConfig as $configType => $optionControlsGroupNames) {

			foreach($optionControlsGroupNames as $optionControlsGroupName => $optionControlsGroupDefinition) {
				if (isset($options[$configType][$optionControlsGroupName])) {
					$optionsValues = $options[$configType][$optionControlsGroupName];
				} else {
					$optionsValues = array();
				}

				$defaultValues = $defaultOptions[$configType][$optionControlsGroupName];

				$optionsControlsGroup = $optionsControlsGroupFactory->createOptionsControlsGroup(
					$configType, $optionControlsGroupName, $optionControlsGroupDefinition, $optionsValues, $defaultValues
				);

				foreach($optionsControlsGroup->getSections() as $section){
					foreach($section->getOptionsControls() as $optionControl){
						if($optionControl->isLessVar()){
							$var = $optionControl->getLessVar();
							if (is_array($var)) {
								$variables += $optionControl->getLessVar();
							}
						}
					}
				}
			}
		}

		return $variables;
	}



	public function getDefaultLessVars()
	{
		$p = aitPaths();
		$defaultLang = AitLangs::getDefaultLocale();

		return array(
			// these names are inconsistent with all other variables
			// they are here for back-compat
			'themeUrl'     => "'{$p->url->theme}'",
			'imgUrl'       => "'{$p->url->img}'",
			'fontsUrl'     => "'{$p->url->fonts}'",
			'designUrl'    => "'{$p->url->theme}/design'",
			'assetsUrl'    => "'{$p->url->assets}'",

			'theme-url'     => "'{$p->url->theme}'",
			'img-url'       => "'{$p->url->img}'",
			'fonts-url'     => "'{$p->url->fonts}'",
			'design-url'    => "'{$p->url->theme}/design'",
			'assets-url'    => "'{$p->url->assets}'",

			'default-lang' => "'{$defaultLang}'",
		);
	}



	public static function lessFnDesignUrl($arg)
	{
		list($type, $delim, $values) = $arg;
		$values[0] = trim($values[0], '\\/');
		$url = aitUrl('theme', "/design/$values[0]");
		$values[0] = $url ? $url : (aitPaths()->url->theme . '/design/' . $values[0]);
		return array($type, $delim, $values);
	}



	public static function lessFnImgUrl($arg)
	{
		list($type, $delim, $values) = $arg;
		$values[0] = ltrim($values[0], '\\/');
		$url = aitUrl('img', "/$values[0]");
		$values[0] = $url ? $url : (aitPaths()->url->img . "/$values[0]");
		return array($type, $delim, $values);
	}



	public static function lessFnFontsUrl($arg)
	{
		list($type, $delim, $values) = $arg;
		$values[0] = trim($values[0], '\\/');
		if(AitUtils::contains($values[0], '?#')){
			$file = strstr($values[0], '?#', true); // fonts-url('awesome/fontawesome-webfonteot?#iefix')
			$hash = strstr($values[0], '?#');
		}elseif(AitUtils::contains($values[0], '?')){
			$file = strstr($values[0], '?', true); // fonts-url('awesome/fontawesome-webfont.svg?v=4.1.0#fontawesomeregular')
			$hash = strstr($values[0], '?');
		}elseif(AitUtils::contains($values[0], '#')){
			$file = strstr($values[0], '#', true); // fonts-url('awesome/fontawesome-webfont.svg#FontAwesome')
			$hash = strstr($values[0], '#');
		}else{
			$file = $values[0]; // fonts-url('awesome/fontawesome-webfont.woff')
			$hash = '';
		}
		$url = aitUrl('fonts', "/{$file}");
		$values[0] = $url ? ($url . $hash) : (aitPaths()->url->fonts . "/$values[0]");
		return array($type, $delim, $values);
	}



	public static function lessFnAssetsUrl($arg)
	{
		list($type, $delim, $values) = $arg;
		$values[0] = trim($values[0], '\\/');
		$url = aitUrl('assets', "/$values[0]");
		$values[0] = $url ? $url : (aitPaths()->url->assets . "/$values[0]");
		return array($type, $delim, $values);
	}




}
