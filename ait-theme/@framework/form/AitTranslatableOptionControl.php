<?php


class AitTranslatableOptionControl extends AitOptionControl
{


	public function getLocalisedValue($subKey = '', $locale = '')
	{
		$value = $this->getValue($subKey);

		if(!empty($locale) and is_array($value) and isset($value[$locale])){
			$value = $value[$locale];
		}elseif(!empty($locale) and is_array($value) and !isset($value[$locale]) and isset($value[AitLangs::getDefaultLocale()])){
			$value = $value[AitLangs::getDefaultLocale()];
		}elseif(is_array($value) and isset($value['en_US'])){
			$value = $value['en_US'];
		}

		// for cases when text option type was changed in config to other type,
		// e.g. text -> code
		if(is_array($value) and empty($locale) and isset($value[AitLangs::getDefaultLocale()])){
			$value = $value[AitLangs::getDefaultLocale()];
		}

		if(is_array($value) and count($value) == 1){
			$value = reset($value);
		}

		return $value;
	}



	public static function prepareDefaultValue($controlDefinition)
	{
		$defaultValue = array();

		foreach(AitLangs::getLocalesList() as $lc){
			if (isset($controlDefinition['default'])) {
				$defaultValue[$lc] = $controlDefinition['default'];
			} else {
				$defaultValue[$lc] = '';
			}
		}

		return $defaultValue;
	}



	protected function label($subKey = '')
	{
		$labelText = $this->getLabelText();

		if($labelText){
			if(isset($this->specialLabels[$this->id])){
				?>
				<span class="ait-label"><?php echo $labelText ?></span>
			<?php
			}else{
				?>
				<label class="ait-label" for="<?php echo $this->getLocalisedIdAttr($subKey, AitLangs::getDefaultLocale()) ?>"><?php echo $labelText; ?></label>
			<?php
			}
		}
	}



	protected function getLocalisedIdAttr($subKey = '', $locale = '')
	{
		$idAttr = $this->getIdAttr($subKey);
		$idAttr .= "-{$locale}";

		return $idAttr;
	}



	protected function getLocalisedNameAttr($subKey = '', $locale = '')
	{
		$configName = $this->parentSection->getParentGroup()->getConfigName();
		$nameAttr = $this->getNameAttr($subKey);

		if(AitConfig::isMainConfigType($configName)){ // do not append lang arg in controls in metaboxes, or other controls
			$nameAttr .= "[{$locale}]";
		}
		elseif ($configName == 'user-metabox') { // user metabox isn't proper MainConfigType but requires localisation anyway
			$nameAttr .= "[{$locale}]";
		}

		return $nameAttr;
	}




}
