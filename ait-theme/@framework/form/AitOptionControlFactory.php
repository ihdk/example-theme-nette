<?php


class AitOptionControlFactory
{


	public function createOptionControl(AitOptionsControlsSection $parentSection, $optionKey, $definition, $value)
	{
		$class = AitOptionControl::resolveClass($definition);

		/** @var AitOptionControl $optionControl */
		$optionControl = new $class($parentSection, $optionKey, $definition, $value);

		if ($optionControl instanceof AitCloneOptionControl) {
			/** @var AitCloneOptionControl $optionControl */

			if(isset($definition['items']) and !empty($definition['items'])){

				// create and set cloneable section template
				$cloneableOptionsControlsSectionTemplate = new AitOptionsControlsSection($parentSection->getParentGroup());

				foreach($definition['items'] as $templateOptionControlKey => $templateOptionControlDefinition){

					$optionControlClass = AitOptionControl::resolveClass($templateOptionControlDefinition);
					$defaultValue = call_user_func(array($optionControlClass, 'prepareDefaultValue'), $templateOptionControlDefinition);

					$templateOptionControl = $this->createOptionControl($cloneableOptionsControlsSectionTemplate, $templateOptionControlKey, $templateOptionControlDefinition, $defaultValue);
					if ($templateOptionControl->isCloneable()) {
						$templateOptionControl->setParentCloneOptionControl($optionControl);
						$cloneableOptionsControlsSectionTemplate->addOptionControl($templateOptionControl);
					}
				}

				$optionControl->setCloneableOptionsControlsSectionTemplate($cloneableOptionsControlsSectionTemplate);

				// create and set (already) cloned options controls sections
				$clonedSectionOptionsControlsDefinitions = $definition['items'];

				if (!is_array($value)) {
					$value = array();
				}

				foreach($value as $i => $clonedOptionsControlsSectionDefinition){

					// add options controls from config that are not yet saved in db
					foreach($clonedSectionOptionsControlsDefinitions as $key => $item){
						if (!isset($clonedOptionsControlsSectionDefinition[$key])) {
							$clonedOptionsControlsSectionDefinition[$key] = isset($definition['default'][$i][$key]) ? $definition['default'][$i][$key] : '';
						}
					}


					$clonedOptionsControlsSection = new AitOptionsControlsSection($parentSection->getParentGroup());

					foreach($clonedOptionsControlsSectionDefinition as $key => $clonedSectionOptionControlValue){
						if (isset($clonedSectionOptionsControlsDefinitions[$key])) {
							$clonedOptionControl = $this->createOptionControl($clonedOptionsControlsSection, $key, $clonedSectionOptionsControlsDefinitions[$key], $clonedSectionOptionControlValue);
							if ($clonedOptionControl->isCloneable()) {
								$clonedOptionControl->setParentCloneOptionControl($optionControl);
								$clonedOptionsControlsSection->addOptionControl($clonedOptionControl);
							}
						}
					}

					$optionControl->addClonedOptionsControlsSection($clonedOptionsControlsSection);
				}

				$optionControl->updateLessVar($value);
			}
		}


		return $optionControl;
	}

}
