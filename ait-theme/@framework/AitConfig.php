<?php


/**
 * Handles work with config files, its loading and proccessing
 */
class AitConfig extends NObject
{

	/**
	 * For caching result of methods
	 * @var internal
	 */
	protected $storage = array();



	public function __construct()
	{
		add_action('pll_after_language_switch', array($this, 'onAfterLanguageSwitch'));
	}



	/**
	 * Loads main configs. Uses AitCache for better performance
	 * @return array Raw encoded neon configs as array
	 */
	protected function loadConfig()
	{
		$userId = get_current_user_id();
		if($value = AitCache::load("@raw-config-$userId")){
			$rawConfig = $value;
		}else{
			$rawConfig = $this->loadMainConfigs();
			AitCache::save("@raw-config-$userId", $rawConfig, array('files' => array_values($this->getMainConfigFiles())));
		}

		return $rawConfig;
	}



	/**
	 * Gets main configs as array, aka raw configs
	 * @return array
	 */
	public function getRawConfig()
	{
		if(!isset($this->storage['raw-config']))
			$this->storage['raw-config'] = $this->loadConfig();

		return $this->storage['raw-config'];
	}



	/**
	 * Gets extracted default values for options in the configs
	 * @param  string $configType Config type: 'theme', 'layout', 'elements'
	 * @return array
	 */
	public function getDefaults($configType = '')
	{
		if(!isset($this->storage['defaults'])){
			$r = $this->processConfig($this->getRawConfig());
			$this->storage['defaults'] = $r['defaults'];
		}

		if(isset($this->storage['defaults'][$configType]))
			return $this->storage['defaults'][$configType];
		else{
			if(empty($configType))
				return $this->storage['defaults'];
			else
				trigger_error("There is no config type '$configType' for " . __METHOD__ . " method");
		}
	}



	/**
	 * Gets full processed config
	 * @param  string $configType Config type: 'theme', 'layout', 'elements'
	 * @return array
	 */
	public function getFullConfig($configType = '')
	{
		if(!isset($this->storage['full-config'])){
			$r = $this->processConfig($this->getRawConfig());
			$this->storage['full-config'] = $r['full-config'];
		}

		if(isset($this->storage['full-config'][$configType]))
			return apply_filters('ait-get-full-config', $this->storage['full-config'][$configType], $configType);
		else{
			if(empty($configType)){
				return apply_filters('ait-get-full-config', $this->storage['full-config'], '');
			}else{
				trigger_error("There is no config type '$configType' for " . __METHOD__ . " method");
			}
		}
	}



	public function getTranslatablesList($configType = '')
	{
		if(!isset($this->storage['translatables-list'])){
			$r = $this->processConfig($this->getRawConfig());
			$this->storage['translatables-list'] = $r['translatables-list'];
		}

		if(isset($this->storage['translatables-list'][$configType]))
			return $this->storage['translatables-list'][$configType];
		else{
			if(empty($configType))
				return $this->storage['translatables-list'];
			else
				trigger_error("There is no config type '$configType' for " . __METHOD__ . " method");
		}
	}



	/**
	 * Gets settings for enabling or disabling AIT Admin pages
	 * @return array
	 */
	public function getAdminConfig($group = '')
	{
		if(!isset($this->storage['admin-config'])){
			$defaults = require aitPaths()->dir->fwConfig . '/admin.php';
			$configFile = aitPath('config', '/admin.neon');
			$config = self::loadRawConfig($configFile, '/admin.neon', true);
			$this->storage['admin-config'] = array_merge($defaults, $config);
		}

		$this->storage['admin-config'] = apply_filters('ait-admin-config', $this->storage['admin-config'], $group);

		if($group){
			if(isset($this->storage['admin-config'][$group]))
				return $this->storage['admin-config'][$group];
			else
				return false;
		}

		return $this->storage['admin-config'];
	}



	public function getDefaultAdminPage()
	{
		$adminPages = $this->getAdminConfig('pages');
		unset($adminPages[0]['sub']);
		return $adminPages[0];
	}



	public function getMainConfigFiles()
	{
		if(!isset($this->storage['main-config-files'])){
			$this->storage['main-config-files'] = apply_filters('ait-main-config-files', array(
				'theme'             => aitPath('config', '/@theme.neon'),
				'layout'            => aitPath('config', '/@layout.neon'),
				'elements'          => aitPath('config', '/@elements.neon'),
				'theme-built-in'    => aitPaths()->dir->fwConfig . '/@theme.php',
				'layout-built-in'   => aitPaths()->dir->fwConfig . '/@layout.php',
				'elements-built-in' => aitPaths()->dir->fwConfig . '/@elements.php',
			));
		}

		return $this->storage['main-config-files'];
	}



	/**
	 * Process config files
	 * @param  array   $rawConfig Raw config decoded with Neon::decode
	 * @param  boolean $force     Force to process without cache
	 * @param  string  $cacheKey  Additional cache key suffix
	 * @param  array   $files     Config files from which config was loaded, for cache invalidation
	 * @return array              Array with keys config, full-config, defaults
	 */
	public function processConfig($rawConfig = array(), $force = false, $cacheKey = '', $files = array(), $extractDefaults = true)
	{
		$f = array();
		$f['files'] = array();

		if($files !== false)
			$f['files'] = empty($files) ? array_values($this->getMainConfigFiles()) : $files;

		if($cacheKey){
			$cacheKey .= md5(implode('', $f['files']));
		}

		if(!$force){
			$userId = get_current_user_id();
			if($value = AitCache::load("@processed-config-$userId" . $cacheKey)){
				return $value;
			}
		}

		$result = array(
			'full-config'  => $this->createFullConfig($rawConfig),
			'defaults'     => $extractDefaults ? $this->extractDefaultsFromConfig($rawConfig) : array(),
		);

		$result['translatables-list'] = $this->extractListOfTranslatableOptions($result['full-config']);

		if(!$force){
			$userId = get_current_user_id();
			AitCache::save("@processed-config-$userId" . $cacheKey, $result, $f);
		}


		return $result;
	}



	/**
	 * Extracts default values from config options
	 * @param  array $rawConfig Raw loaded configs
	 * @return array
	 */
	public function extractDefaultsFromConfig($rawConfig, $withOnlyUsedElements = false)
	{
		$defaults = array();
		$i = 0;

		foreach($rawConfig as $configName => $groups){
			foreach($groups as $groupKey => $groupValues){

				if(!isset($groupValues['options'])){
					$groupValues['options'] = array();
				}

				if($configName == 'elements' and isset($groupValues['used']) and !$groupValues['used'] and $withOnlyUsedElements) continue;

				foreach($groupValues['options'] as $optionKey => $optionControlDefinition){
					if(!$this->isOptionsSection($optionControlDefinition)){
						if(isset($optionControlDefinition['type']) or (!isset($optionControlDefinition['type']) and isset($optionControlDefinition['callback']))){
							$optionControlClass = AitOptionControl::resolveClass($optionControlDefinition);

							$defaultValue = call_user_func(array($optionControlClass, 'prepareDefaultValue'), $optionControlDefinition);

							if($configName == 'elements'){
								$defaults[$configName][$i][$groupKey][$optionKey] = $defaultValue;
							}else{
								$defaults[$configName][$groupKey][$optionKey] = $defaultValue;
							}
						}
					}
				}

				if(self::isMainConfigType($configName) and $configName == 'elements'){
					if(!isset($defaults[$configName][$i][$groupKey])){
						$defaults[$configName][$i][$groupKey] = array();
					}

					$defaults[$configName][$i][$groupKey]['@columns-element-index'] = '';
					$defaults[$configName][$i][$groupKey]['@columns-element-column-index'] = '';
					$defaults[$configName][$i][$groupKey]['@element-user-description'] = '';

				}else{
					if(!isset($defaults[$configName][$groupKey])){
						$defaults[$configName][$groupKey] = array();
					}
				}

				if(self::isMainConfigType($configName) and $configName == 'elements'){
					$i++;
				}
			}

			if(!isset($defaults[$configName])){
				$defaults[$configName] = array();
			}
		}

		if(empty($defaults)){
			$defaults[key($rawConfig)] = array();
		}

		return $defaults;
	}



	public function extractListOfTranslatableOptions($fullConfig)
	{
		$translatables = array();


		foreach($fullConfig as $configType => $groups){
			$translatables[$configType] = array();

			foreach($groups as $groupKey => $groupValues){
				$groupId = $configType == 'elements' ? $groupValues['@element'] : $groupKey;

				foreach($groupValues['@options'] as $sections){

					foreach($sections as $optionKey => $option){
						if ($optionKey == '@section') continue;
						$optionControlClass = AitOptionControl::resolveClass($option);
						if ($optionControlClass == 'AitTranslatableOptionControl' || is_subclass_of($optionControlClass, 'AitTranslatableOptionControl')){
							$translatables[$configType][$groupId][$optionKey] = true;
						} elseif($optionControlClass == 'AitCloneOptionControl' || is_subclass_of($optionControlClass, 'AitCloneOptionControl')){
							foreach($option['items'] as $k => $clone){
								if(is_subclass_of(AitOptionControl::resolveClass($clone), 'AitTranslatableOptionControl')){
									$translatables[$configType][$groupId][$optionKey][$k] = true;
								}
							}
						}
					}
				}
			}
		}

		return $translatables;
	}



	/**
	 * Creates full config from raw config
	 * @param  array $rawConfig Raw loaded configs
	 * @return array [type]            [description]
	 */
	public function createFullConfig($rawConfig)
	{
		$fullConfig = array();

		$j = $sectionIndex = 0;

		// config type: theme, layout, elements
		foreach($rawConfig as $configType => $groups){

			if($configType == 'elements'){
				$fullConfig['elements'] = $this->createElementsFullConfig($groups);
			}else{
				foreach($groups as $groupKey => $groupValues){

					$fullConfig[$configType][$groupKey] = $this->convertGroupForFullConfig($groupKey, $groupValues);

					if(!isset($groupValues['options'])){
						$fullConfig[$configType][$groupKey]['@options'] = array();
						$groupValues['options'] = array();
					}

					$hasSections = count(array_filter(array_keys($groupValues['options']), 'is_numeric')) > 0;

					if(!$hasSections and !empty($groupValues['options'])){
						$fullConfig[$configType][$groupKey]["@options"][0]["@section"] = $this->getOptionsSection(true);
					}elseif(empty($groupValues['options'])){
						$fullConfig[$configType][$groupKey]["@options"] = array();
					}

					$currentSectionIsAdvanced = false;

					// options in current group
					foreach($groupValues['options'] as $optionKey => $optionValue){

						if($this->isOptionsSection($optionValue)){
							$sectionIndex = $j;
							$fullConfig[$configType][$groupKey]["@options"][$sectionIndex]["@section"] = $this->getOptionsSection($optionValue);
							$currentSectionIsAdvanced = $fullConfig[$configType][$groupKey]["@options"][$sectionIndex]["@section"]->allAreAdvanced;
						}else{
							if(!isset($optionValue['type']) and !isset($optionValue['callback'])){
								trigger_error("Option '{$configType}.{$groupKey}.{$optionKey}' does not have 'type' parameter set.", E_USER_WARNING);
							}else{
								if($j == 0) // there is no first section, so fake it
									$fullConfig[$configType][$groupKey]["@options"][0]["@section"] = $this->getOptionsSection(true);

								if($currentSectionIsAdvanced and isset($optionValue['basic']))
									unset($optionValue['basic']);

								if(isset($groupValues['text-domain'])){
									$optionValue['text-domain'] = $groupValues['text-domain'];
								}
								$fullConfig[$configType][$groupKey]["@options"][$sectionIndex][$optionKey] = $optionValue;
							}
						}
						$j++;
					}

					if(isset($fullConfig[$configType][$groupKey]['@options'][0]) and count($fullConfig[$configType][$groupKey]['@options'][0]) == 1){
						unset($fullConfig[$configType][$groupKey]);
					}

					$j = $sectionIndex = 0;
				}

				if(!isset($fullConfig[$configType]))
					$fullConfig[$configType] = array();


			}
		}

		if(empty($fullConfig))
			$fullConfig[key($rawConfig)] = array();

		return $fullConfig;
	}



	/**
	 * Creates full config of elements configs
	 * @param  array $elements Raw config of elements
	 * @return array
	 */
	public function createElementsFullConfig($elements)
	{
		$return = array();
		$row = $cols = $j = $sectionIndex = 0;

		$i = 0;

		foreach($elements as $elKey => $elValues){
			$return[$i] = $this->convertGroupForFullConfig($elKey, $elValues, true);
			$return[$i]['@element'] = $elKey;

			$hasSections = count(array_filter(array_keys($elValues['options']), 'is_numeric')) > 0;

			if(!$hasSections and !empty($elValues['options']))
				$return[$i]["@options"][0]["@section"] = $this->getOptionsSection(true);
			elseif(empty($elValues['options']))
				$return[$i]["@options"] = array();

			// options in current group
			foreach($elValues['options'] as $optionKey => $optionValue){

				if($this->isOptionsSection($optionValue)){
					$sectionIndex = $j;
					$return[$i]["@options"][$sectionIndex]["@section"] = $this->getOptionsSection($optionValue);
				}else{
					if(!isset($optionValue['type']) and !isset($optionValue['callback'])){
						trigger_error("Option 'elements.{$elKey}.{$optionKey}' does not have 'type' parameter set.", E_USER_WARNING);
					}else{
						if($j == 0){
							// there is no first section, so fake it
							$return[$i]["@options"][0]["@section"] = $this->getOptionsSection(true);
						}

						$return[$i]["@options"][$sectionIndex][$optionKey] = $optionValue;
					}
				}
				$j++;
			}

			$hiddenSection = $sectionIndex + 1;

			// adds hidden column info and optional user description of element (shown next to element title)
			if(!isset($return[$i]["@options"][$hiddenSection])){
				$return[$i]["@options"][$hiddenSection]["@section"] = $this->getOptionsSection(true, true);
				$return[$i]["@options"][$hiddenSection] = array_merge($return[$i]["@options"][$hiddenSection], array(
					'@columns-element-index' => array('type' => 'hidden', 'basic' => true),
					'@columns-element-column-index' => array('type' => 'hidden', 'basic' => true),
					'@element-user-description' => array('type' => 'hidden', 'basic' => true)
				));
			}

			$i++;
			$j = $sectionIndex = 0;
		}

		return $return;
	}



	public function mergeIncludedConfigIfAny($options, $groupKey, $isElements = false)
	{
		if(isset($options['@include'])){
			$includedConfig = $this->includeConfig($options['@include'], $groupKey, $isElements);
			unset($options['@include']);
			$includedConfig = array_reverse($includedConfig);
			foreach($includedConfig as $c){
				$options = array_replace_recursive($c, $options);
			}
		}

		return $options;
	}



	public function includeConfig($includes, $group, $inElements = false)
	{
		$includes = (array) $includes;
		$return = array();

		if($inElements){
			foreach($includes as $include){
				$inc = $this->parseIncludeStatement($include);

				$file = aitPath('elements', "/@common/{$inc->file}");
				if($file === false){
					trigger_error("There is no cofig file '@common/{$inc->file}' for including in element '{$group}'", E_USER_WARNING);
					$return[$inc->file] = array();
				}else{
					$includedConfig = self::loadRawConfig($file);
					$this->storage['main-config-files']["@common/{$inc->file}"] = $file;

					// Generate unique "ID" for sections instead of indexed ones.
					// It will prevent replacing sections in @common configs
					// with those from where is common configs are included - element's config
					$counter = 0;
					foreach($includedConfig as $k => $v){
						if($this->isOptionsSection($v)){
							$counter++;
							NArrays::renameKey($includedConfig, $k, $k . $group . $inc->file);
						}
					}

					// Add empty section to the end of options list if there is no
					// empty section at the end yet
					if($counter > 0 and !isset($includedConfig[$k . $group . $inc->file])){
						$nn = new NNeonEntity;
						$nn->value = 'section';
						$nn->attributes = array();
						$includedConfig[$counter . $group . $inc->file] = $nn;
					}

					if(empty($inc->options)){
						if(empty($inc->excludeOptions)){
							$return[$inc->file] = $includedConfig;
						}else{
							$x = array_diff_key($includedConfig, $inc->excludeOptions);
							$return[$inc->file] = $x;
						}
					}else{
						$return[$inc->file] = array_intersect_key($includedConfig, $inc->options);
					}
				}
			}
		}else{
			// theme.neon, layout.neon configs
			// not implemented yet...
		}

		return $return;
	}



	protected function parseIncludeStatement($statement)
	{
		$return = new stdClass;
		$return->file = $statement;
		$return->options = array();
		$return->excludeOptions = array();

		$statement = trim($statement, '\\/');

		if(AitUtils::contains($statement, '#')){
			$parts = explode('#', $statement);
			$return->file = $parts[0];
			if(isset($parts[1]) and $parts[1] != ''){
				if(AitUtils::startsWith($parts[1], 'exclude:')){
					$parts[1] = str_replace('exclude:', '', $parts[1]);
					$options = explode(',', $parts[1]);
					$options = array_map('trim', $options);
					$return->excludeOptions = array_combine($options, $options);
				}else{
					$options = explode(',', $parts[1]);
					$options = array_map('trim', $options);
					$return->options = array_combine($options, $options);
				}
			}
		}

		return $return;
	}



	/**
	 * Converts raw options group in config to full config
	 * @param  string  $groupKey    Group key
	 * @param  array  $groupData    Group items
	 * @param  boolean $isElement   If processing group is the element
	 * @return array
	 */
	public function convertGroupForFullConfig($groupKey, $groupData, $isElement = false)
	{
		$return = array();
		$hasReset = (!isset($groupData['reset']) or (isset($groupData['reset']) and $groupData['reset'] !== false));
		$hasImport = (isset($groupData['import']) and $groupData['import'] === true);
		$hasUsed  = (!isset($groupData['used']) or (isset($groupData['used']) and $groupData['used'] !== false));
		$hasDisabled = (isset($groupData['disabled']) and $groupData['disabled'] === true);
		if($isElement and isset($groupData['package']) and isset($groupData['package'][AIT_THEME_PACKAGE]) and $groupData['package'][AIT_THEME_PACKAGE] == false){
			$hasDisabled = true;
		}

		$title    = isset($groupData['title']) ? $groupData['title'] : false;

		if($title){
			if(is_string($title)){
				$_translate = '__';
				$title = $_translate($title, 'ait-admin');
			}elseif($title instanceof NNeonEntity){
				if($title->value == '_x' and !empty($title->attributes)){
					$text = $title->attributes[0];
					$context = $title->attributes[1];
					$_translate = '_x';
					$title = $_translate($text, $context, 'ait-admin');
				}
			}
			$return['@title'] = $title;
		}

		$return['@reset'] = $hasReset;
		$return['@import'] = $hasImport;
		$return['@disabled'] = $hasDisabled;

		$return['@configuration'] = isset($groupData['configuration']) ? $groupData['configuration'] : array();

		$icon = isset($groupData['icon']) ? $groupData['icon'] : 'fa-align-left';
		$color = isset($groupData['color']) ? $groupData['color'] : '#3ba6bd';

		if(isset($return['@configuration']['sortable']) && $return['@configuration']['sortable'] == false){
			$icon = 'fa-map-pin';
			$color = '#dbdbdb';
		}

		$return['@icon'] = $icon;
		$return['@color'] = $color;

		if($isElement){
			$return['@used'] = $hasUsed;
		}

		return $return;
	}



	/**
	 * Wheter given option is options section
	 * @param  mixed  $value Option item, in Neon file as "- section" or "- section(id:..., title:..., help:....)"
	 * @return boolean
	 */
	public function isOptionsSection($value)
	{
		if(is_string($value) and $value === 'section'){
			return true;
		}

		if($value instanceof NNeonEntity){
			return true;
		}

		if(is_array($value) and (in_array('section', $value, true) or isset($value['section']))){
			return true;
		}

		return false;
	}



	/**
	 * Gets options section
	 * @param  true|NNeonEntity $value True if we want empty section, otherwise NNeonEntity object
	 * @return stdClass
	 */
	protected function getOptionsSection($value, $hidden = false)
	{
		$return = new stdClass;
		$return->title = false;
		$return->help = false;
		$return->id = false;
		$return->hidden = $hidden;
		$return->allAreAdvanced = false;
		$return->capabilities = false;

		if($value === true){
			return $return;
		}

		if($value === 'section'){
			return $return;
		}

		$return->title          = $this->_getSectionValue($value, 'title');
		$return->help           = $this->_getSectionValue($value, 'help');
		$return->id             = $this->_getSectionValue($value, 'id');
		$return->allAreAdvanced = $this->_getSectionValue($value, 'advanced', true);
		$return->capabilities   = $this->_getSectionValue($value, 'capabilities', true);

		return $return;
	}



	protected function _getSectionValue($section, $key, $isBool = false)
	{
		$value = '';
		$v = array();

		if($section instanceof NNeonEntity){
			$v = $section->attributes;
		}elseif(isset($section['section'])){
			$v = $section['section'];
		}elseif(in_array('section', $section, true)){
			$v = array();
		}

		if(isset($v[$key])){
			if($isBool){
				$value = (bool) $v[$key];
			}else{
				$value = $v[$key];
			}
		}

		return $value;
	}



	// =======================================
	// Helpers
	// ---------------------------------------



	/**
	 * Loads main configs
	 * @return array Loaded configs
	 */
	public function loadMainConfigs()
	{
		$f = $this->getMainConfigFiles();

		$config = array();

		$config['theme'] = $this->loadThemeConfig($f['theme'], $f['theme-built-in']);
		$config['layout'] = $this->loadLayoutConfig($f['layout'], $f['layout-built-in']);
		$config['elements'] = $this->loadElementsConfigs($f['elements'], $f['elements-built-in']);

		// extend main config files via filter called from ait plugins
		$pluginsConfigTypes = apply_filters('ait-config-types',  array());

		foreach ($pluginsConfigTypes as $key) {
			if(isset($config[$key])) continue;
			$config[$key] = self::loadRawConfig($f[$key]);
		}

		return $config;
	}



	/**
	 * Loads config from Neon config file.
	 * @param  string $file        Absolute path to config file
	 * @param  string $builtInFile Absolute path to built-in config file
	 * @return array               Parsed Neon config
	 */
	public function loadThemeConfig($file, $builtInFile)
	{
		$config = self::loadRawConfig($file);
		$config = apply_filters('ait-theme-config', $config);

		$config2 = require $builtInFile;
		$config2 = apply_filters('ait-theme-builtin-config', $config2);

		$return = array_replace_recursive($config, $config2);

		return $return;
	}



	/**
	 * Loads config from Neon config file.
	 * @param  string $file        Absolute path to config file
	 * @return array               Parsed Neon config
	 */
	public function loadElementsConfigs($file, $builtInFile)
	{
		if($file === false)
			$localConfig = array();
		else
			$localConfig = self::loadRawConfig($file);

		$localConfig = apply_filters('ait-elements-config', $localConfig);

		$builtInConfig = require $builtInFile;

		$builtInConfig = apply_filters('ait-elements-builtin-config', $builtInConfig);

		$config = array_replace_recursive($builtInConfig, $localConfig);

		$el = $unsortable = $sortable = array();

		foreach($config as $elId => $params){

			if(!current_theme_supports("ait-element-{$elId}")) continue;

			if(!isset($params['disabled'])){
				$params['disabled'] = false;
			}elseif(isset($params['disabled']) and $params['disabled'] === true){
				continue;
			}

			$el = $params;
			$el['used'] = false;
			$el['options'] = array();

			$optFilename = "/{$elId}/{$elId}.options.neon";
			$optFile = aitPath('elements', $optFilename);

			$optFile = apply_filters('ait-element-options-file', $optFile, $elId);
			$optFilename = apply_filters('ait-element-options-filename', $optFilename, $elId);

			$elOptions = self::loadRawConfig($optFile, $optFilename, true); // element do not need options config, so it is options-less element

			if($elOptions){
				$el['options'] = $this->mergeIncludedConfigIfAny($elOptions, $elId, true);
			}

			if($optFile){
				$this->storage['main-config-files']["{$elId}-options"] = $optFile;
			}else{
				$el['disabled'] = true;
			}

			// unsortable elements must be allways used
			if((isset($el['configuration']['sortable']) and $el['configuration']['sortable'] === false)){
				$el['used'] = true;
				$unsortable[$elId] = $el;
			}else{
				$sortable[$elId] = $el;
			}
		}

		$donotchange = array(
			'used' => true,
			'configuration' => array(
				'cloneable' => false,
				'sortable' => true,
			)
		);

		$sortable['content'] = array_replace_recursive($sortable['content'], $donotchange);
		$sortable['comments'] = array_replace_recursive($sortable['comments'], $donotchange);


		// add sidebars boundaries

		$sortable = array_merge(
			array('sidebars-boundary-start' => array(
					'configuration' => array('sortable' => true, 'no-base-style' => true, 'no-paths' => true),
					'options' => array(
						'sidebars-boundary-start' => array('type' => 'hidden'),
					),
				)
			),
			$sortable,
			array('sidebars-boundary-end' => array(
					'configuration' => array('sortable' => true, 'no-base-style' => true, 'no-paths' => true),
					'options' => array(
						'sidebars-boundary-end' => array('type' => 'hidden'),
					),
				)
			)
		);

		$allElements = array_merge($unsortable, $sortable);

		return apply_filters('ait-load-elements-configs', $allElements);
	}



	/**
	 * Loads config from Neon config file.
	 * @param  string $file        Absolute path to config file
	 * @param  string $builtInFile Absolute path to built-in config file
	 * @return array               Parsed Neon config
	 */
	public function loadLayoutConfig($file, $builtInFile)
	{
		$config = self::loadRawConfig($file);
		$config = apply_filters('ait-layout-config', $config);

		$config2 = require $builtInFile;
		$config2 = apply_filters('ait-layout-builtin-config', $config2);

		$return = array_replace_recursive($config, $config2);

		return $return;
	}



	/**
	 * Checks if given config type is main config type. Main config types are 'theme', 'layout', 'elements'
	 * @param  string  $type Config type
	 * @return boolean
	 */
	public static function isMainConfigType($type)
	{
		return in_array($type, self::getMainConfigTypes());
	}



	public static function getMainConfigTypes()
	{
		$configTypes = array('theme', 'layout', 'elements');
		return apply_filters('ait-config-types', $configTypes);
	}



	/**
	 * Loads config from Neon config file.
	 * @param  string $file     Absolute path to config file
	 * @param  string $filename Name of file which will be included, uses for debugging purposes, it's showed when $file is missing
	 * @return array       Parsed Neon config
	 */
	public static function loadRawConfig($file, $filename = '', $optional = false)
	{
		if($file === false){
			if(!$optional){
				trigger_error("Config file '{$filename}' doesn't exist.", E_USER_WARNING);
			}
			return array();
		}

		if(AitUtils::endsWith($file, '.php')){

			$config = include $file;

		}else{

			$content = @file_get_contents($file);

			if($content === false){
				trigger_error("Config file '{$filename}' is unreadable.", E_USER_WARNING);
				return array();
			}

			$config = (array) NNeon::decode($content);
		}

		return $config;
	}



	private function deleteConfigCachedFiles($userId)
	{
		AitCache::remove("@raw-config-$userId");
		AitCache::remove("@processed-config-$userId");
	}



	public function onAfterLanguageSwitch($arguments)
	{
		$this->deleteConfigCachedFiles($arguments['user']);
	}


}
