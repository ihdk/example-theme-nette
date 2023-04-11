<?php


class AitElementsManager extends NObject
{
	protected $prototypes = array();

	protected $assetsManager;

	protected $optionsControlsGroupFactory;



	public function __construct($fullConfigs, $defaults, AitAssetsManager $assetsManager, AitOptionsControlsGroupFactory $optionsControlsGroupFactory)
	{
		$this->assetsManager = $assetsManager;
		$this->optionsControlsGroupFactory = $optionsControlsGroupFactory;

		$this->createPrototypes($fullConfigs, $defaults);

		add_action('init', array($this, 'onInit'), 11);
	}



	public function getPrototypes()
	{
		return $this->prototypes;
	}



	public function getPrototype($element)
	{
		if(isset($this->prototypes[$element])){
			return $this->prototypes[$element];
		}
		return false;
	}



	protected function createPrototypes($fullConfigs, $defaults)
	{

		foreach($fullConfigs as $i => $elementsFullConfig) {
			$elementId = $elementsFullConfig['@element'];

			$element = $this->createPrototype($elementId, $elementsFullConfig, $defaults[$i][$elementId]);

			if($element){
				$this->prototypes[$elementId] = $element;
			}
		}
	}



	public function createPrototype($elementId, $fullConfig, $defaults)
	{
		$className = 'AitElement';

		$class = AitUtils::id2class($elementId, 'Element');
		$classfile = aitPath('elements', "/{$elementId}/{$class}.php");

		if($classfile !== false){
			$className = $class;
		}

		if(isset($fullConfig['@configuration']['class']) and !empty($fullConfig['@configuration']['class'])){
			$className = $fullConfig['@configuration']['class'];
		}

		if(class_exists($className)){
			return new $className($elementId, $fullConfig, $defaults);
		}else{
			return null;
		}
	}



	public function createElementsFromOptions($list, $oid = '', $onFrontend = false)
	{
		/** @var AitElement[] $elements */
		$elements = array();

		foreach($list as $i => $elementOptions){
			$elId = key($elementOptions);
			if(!current_theme_supports("ait-element-{$elId}")) continue;

			try{
				$elements[$i] = $this->createElement($elId, $i, $oid, $elementOptions[$elId]);

				if($onFrontend){
					if(($elements[$i]->isEnabled() or AIT_THEME_PACKAGE === 'basic') and $elements[$i]->isDisplay()){
						$this->assetsManager->addAssets($elements[$i]->getFrontendAssets(), array('paths' => $elements[$i]->getPaths()));
						$this->assetsManager->addInlineStyleCallback(array(&$elements[$i], 'getInlineStyle'));
					}
				}
			}catch(Exception $e){
				// skip nonexistent elements
			}
		}
		return $elements;
	}



	public function onInit()
	{
		if(aitIsPluginActive('toolkit')){
			foreach($this->prototypes as $id => $element){
				foreach($element->getCpts() as $cptId => $enabled){
					if(aitManager('cpts')->has($cptId)){
						$this->prototypes[$id]->setCpt($cptId);
					}
				}
			}
		}
	}



	public function createElement($id, $instanceNumber, $oid, $options)
	{
		if(isset($this->prototypes[$id])){

			/** @var AitElement $element */
			$element = unserialize(serialize($this->prototypes[$id]));

		}else{
			throw new Exception('Could not create element with id: ' . $id);
		}

		$element->setInstanceNumber($instanceNumber);
		$element->setOid($oid);
		$element->setOptions($options);


		$optionsControlsGroupDefinition = array();
		$optionsControlsGroupDefinition['@reset'] = true;
		$optionsControlsGroupDefinition['@options'] = $element->getConfig('@options');
		$values = $element->getOptions();
		$defaultValues = $element->getOptionsDefaults();

		$optionsControlsGroup = $this->optionsControlsGroupFactory->createOptionsControlsGroup('elements', $id, $optionsControlsGroupDefinition, $values, $defaultValues, $instanceNumber);
		$element->setOptionsControlsGroup($optionsControlsGroup);

		return $element;
	}



	public function isElementSidebarsBoundary($elementId)
	{
		return $elementId == 'sidebars-boundary-start' || $elementId == 'sidebars-boundary-end';
	}

}
