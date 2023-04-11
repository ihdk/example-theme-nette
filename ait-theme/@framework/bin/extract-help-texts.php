<?php


include __DIR__ . '/../vendor/nette.min.inc';

$configsPath = realpath(__DIR__ . '/../../config');
$fwConfigsPath = realpath(__DIR__ . '/../config');
$elementsDir = realpath(__DIR__ . '/../../elements');

$configs = array(
	'theme' => loadConfig("$configsPath/@theme.neon"),
	'layout' => loadConfig("$configsPath/@layout.neon"),
	'elements' => loadElementsConfigs("$configsPath/@elements.neon", "$fwConfigsPath/@elements.php"),
);

$output = extractHelpTexts($configs);

file_put_contents("$configsPath/help-texts.php", $output);



function extractHelpTexts($configs)
{
	$strings = array();

	foreach($configs as $configType => $config){
		foreach($config as $groupKey => $group){
			$group['options'] = mergeIncludedConfigIfAny($group['options'], $groupKey, true);
			foreach($group['options'] as $optionKey => $params){
				if(isOptionsSection($params)){
					$strings[$configType][$groupKey]['options'][$optionKey] = '';
				}else{
					if($params['type'] == 'clone'){
						foreach($params['items'] as $k => $v){
							if(isset($v['help'])){
								$strings[$configType][$groupKey]['options'][$optionKey][$k] = $v['help'];
							}
						}
						if(isset($params['help'])){
							$strings[$configType][$groupKey]['options'][$optionKey]['clone'] = $params['help'];
						}else{
							$strings[$configType][$groupKey]['options'][$optionKey]['clone'] = '';
						}
					}elseif(isset($params['help'])){
						$strings[$configType][$groupKey]['options'][$optionKey] = $params['help'];
					}else{
						$strings[$configType][$groupKey]['options'][$optionKey] = '';
					}
				}
			}
		}
	}

	$strings = array_map_recursive(function($item){
		if(is_string($item)){
			if($item === ''){
				return "''";
			}else{
				$item = json_encode($item);
				$return = "__($item, 'ait-admin')";
				$return = str_replace('\\/', '/', $return);
				return $return;
			}
		}
		return $item;
	}, $strings);

$output = '<?php';

	$output .= "\n\n\n\$strings = array();\n\n";

	foreach($strings as $configType => $string){
		$output .= "\$strings['$configType'] = array(\n";
		foreach($string as $groupKey => $group){
			$output .= "\t'$groupKey' => array(\n";

			foreach($group['options'] as $optionKey => $helpText){
				if(is_array($helpText)){
					$cloneHelp = '';
					if(isset($helpText['clone'])){
						$cloneHelp = $helpText['clone'];
						unset($helpText['clone']);
					}
					$output .= "\t\t'$optionKey' => array(\n\t\t\t'items' => array(\n";
					foreach($helpText as $k => $v){
						$output .= "\t\t\t\t'$k' => $v,\n";
					}
					$output .= "\t\t\t),";
					$output .= "\n\t\t\t'help' => $cloneHelp,";
					$output .= "\n\t\t),\n";
				}elseif(is_numeric($optionKey) or NStrings::endswith($optionKey, '.neon')){

				}else{
					$output .= "\t\t'$optionKey' => $helpText,\n";
				}
			}

			$output .= "\t),\n";
		}
		$output .= ");\n\n\n\n";
	}

	$output .= "return \$strings;\n";

	return $output;
}





function __($s)
{
	return $s;
}


function _x($s)
{
	return $s;
}



function loadConfig($file)
{
	$content = @file_get_contents($file);

	if($content === false){
		trigger_error("Config file '{$file}' is unreadable.", E_USER_WARNING);
		return array();
	}

	$config = (array) NNeon::decode($content);

	return $config;
}



function loadElementsConfigs($file, $builtInFile)
{
	global $elementsDir;

	$return = array();

	if($file === false)
		$localConfig = array();
	else
		$localConfig = loadConfig($file);

	$builtInConfig = require $builtInFile;

	$el = $unsortable = $sortable = array();

	foreach($builtInConfig as $elId => $params){

		if(isset($localConfig[$elId]) and is_array($localConfig[$elId])){
			$params = array_replace_recursive($params, $localConfig[$elId]);
		}

		$el = $params;
		$el['options'] = array();

		$optFile = "{$elementsDir}/{$elId}/{$elId}.options.neon";

		if(!file_exists($optFile)) continue;

		$elOptions = loadConfig($optFile);

		if($elOptions){
			$el['options'] = $elOptions;
		}

		$return[$elId] = $el;
	}

	return $return;
}



function mergeIncludedConfigIfAny($options, $groupKey, $isElements = false)
{
	if(isset($options['@include'])){
		$includedConfig = includeConfig($options['@include'], $groupKey, $isElements);
		unset($options['@include']);
		$includedConfig = array_reverse($includedConfig);
		foreach($includedConfig as $c){
			$options = array_replace_recursive($c, $options);
		}
	}

	return $options;
}



function includeConfig($includes, $group, $inElements = false)
{
	global $elementsDir;

	$includes = (array) $includes;
	$return = array();

	if($inElements){
		foreach($includes as $include){
			$inc = parseIncludeStatement($include);

			$file = "{$elementsDir}/@common/{$inc->file}";

			$includedConfig = loadConfig($file);

			// Generate unique "ID" for sections instead of indexed ones.
			// It will prevent replacing sections in @common configs
			// with those from where is common configs are included - element's config
			$counter = 0;
			$new = array();
			foreach($includedConfig as $k => $v){
				if(isOptionsSection($v)){
					$counter++;
					$new[$k . $group . $inc->file] = $v;
				}else{
					$new[$k] = $v;
				}
			}

			// Add empty section to the end of options list if there is no
			// empty section end the end yet

			if($counter > 0 and !isset($new[$k . $group . $inc->file])){
				$nn = new NNeonEntity;
				$nn->value = 'section';
				$nn->attributes = array();
				$new[$counter . $group . $inc->file] = $nn;
			}

			if(empty($inc->options)){
				$return[$inc->file] = $new;
			}else{
				$return[$inc->file] = array_intersect_key($new, $inc->options);
			}
		}
	}else{
		// not implemented yet...
	}

	return $return;
}



function parseIncludeStatement($statement)
{
	$return = new stdClass;
	$return->file = $statement;
	$return->options = array();

	$statement = trim($statement, '\\/');

	if(NStrings::contains($statement, '#')){
		$parts = explode('#', $statement);
		$return->file = $parts[0];
		if(isset($parts[1]) and $parts[1] != ''){
			$options = explode(',', $parts[1]);
			$options = array_map('trim', $options);
			$return->options = array_combine($options, $options);
		}
	}

	return $return;
}



 function isOptionsSection($value)
	{
		return ((is_string($value) and $value == 'section') or ($value instanceof NNeonEntity));
	}




function array_map_recursive($callback, $array)
{
	foreach($array as $key => $value){
		if(is_array($array[$key])){
			$array[$key] = array_map_recursive($callback, $array[$key]);
		}else{
			$array[$key] = call_user_func($callback, $array[$key]);
		}
	}
	return $array;
}

