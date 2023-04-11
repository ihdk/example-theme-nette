<?php


class AitOptionsControlsGroupFactory
{


	protected $optionsControlFactory;


	public function __construct(AitOptionControlFactory $optionsControlFactory)
	{
		$this->optionsControlFactory = $optionsControlFactory;
	}



	public function createOptionsControlsGroup($configName, $groupId, $groupDefinition, $values, $defaultValues, $index = null)
	{
		$group = new AitOptionsControlsGroup();

		$group->setId($groupId);
		$group->setIndex($index);
		$group->setConfigName($configName);
		$group->setReset(isset($groupDefinition['@reset']) ? $groupDefinition['@reset'] : false);
		$group->setImport(isset($groupDefinition['@import']) ? $groupDefinition['@import'] : false);
		$group->setDisabled(isset($groupDefinition['@disabled']) ? $groupDefinition['@disabled'] : false);
		$group->setConfiguration(isset($groupDefinition['@configuration']) ? $groupDefinition['@configuration'] : false);
		$group->setUsed(isset($groupDefinition['@used']) ? $groupDefinition['@used'] : false);
		$group->setAdvancedEnabled(isset($values['@enabledAdvanced']) ? $values['@enabledAdvanced'] : 0);

		foreach(array_values($groupDefinition['@options']) as $sectionDefinition){

			$section = new AitOptionsControlsSection($group);

			foreach($sectionDefinition as $optionControlKey => $optionControlDefinition){

				if($optionControlKey == '@section'){
					$section->setTitle($optionControlDefinition->title);
					$section->setHelp($optionControlDefinition->help);
					$section->setId($optionControlDefinition->id);
					$section->setHidden($optionControlDefinition->hidden);
					$section->setAllAreAdvanced($optionControlDefinition->allAreAdvanced);
					if(!empty($optionControlDefinition->capabilities)){
						$section->setCapabilityEnabled($optionControlDefinition->capabilities);
						$section->setCapabilityName($group->getId()."_".$optionControlDefinition->id);
					}
				}else{

					if(isset($values[$optionControlKey])){
						$value = $values[$optionControlKey];
					}elseif(isset($defaultValues[$optionControlKey])){
						$value = $defaultValues[$optionControlKey];
					}else{
						$value = null;
					}

					$optionControl = $this->optionsControlFactory->createOptionControl($section, $optionControlKey, $optionControlDefinition, $value);

					$section->addOptionControl($optionControl);
				}
			}

			$group->addSection($section);
		}

		return $group;
	}





}
