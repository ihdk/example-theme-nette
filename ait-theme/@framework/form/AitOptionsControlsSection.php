<?php


class AitOptionsControlsSection
{

	protected $title;
	protected $help;
	protected $id;
	protected $hidden;
	protected $allAreAdvanced;
	protected $configName;
	protected $advancedEnabled;

	protected $capabilityEnabled;
	protected $capabilityName;

	/** @var AitOptionsControlsGroup */
	protected $parentGroup;

	/** @var AitOptionControl[] */
	protected $optionsControls = array();


	public function __construct(AitOptionsControlsGroup $parentGroup)
	{
		$this->parentGroup = $parentGroup;
	}



	public function setAllAreAdvanced($allAreAdvanced)
	{
		$this->allAreAdvanced = $allAreAdvanced;
	}



	public function areAllAdvanced()
	{
		return $this->allAreAdvanced;
	}



	public function setHelp($help)
	{
		$this->help = $help;
	}



	public function getHelp()
	{
		return $this->help;
	}



	public function setHidden($hidden)
	{
		$this->hidden = $hidden;
	}



	public function isHidden()
	{
		return $this->hidden;
	}



	public function setId($id)
	{
		$this->id = $id;
	}



	public function getId()
	{
		return $this->id;
	}



	public function setTitle($title)
	{
		$this->title = $title;
	}



	public function getTitle()
	{
		return $this->title;
	}


	public function setCapabilityEnabled($bool){
		$this->capabilityEnabled = (boolean)$bool;
	}

	public function isCapabilityEnabled(){
		return $this->capabilityEnabled;
	}

	public function setCapabilityName($name){
		$this->capabilityName = $name;
	}

	public function getCapabilityName(){
		return $this->capabilityName;
	}


	public function addOptionControl(AitOptionControl $optionControl)
	{
		$this->optionsControls[$optionControl->getKey()] = $optionControl;
	}



	public function getOptionsControls()
	{
		return $this->optionsControls;
	}



	public function getOptionControl($key)
	{
		return $this->optionsControls[$key];
	}

	public function setParentGroup($group)
	{
		$this->parentGroup = $group;
	}


	public function getParentGroup()
	{
		return $this->parentGroup;
	}

}
