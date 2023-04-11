<?php


/**
 * Class AitOptionsControlsGroup represents topmost container that wraps sections of options controls
 *
 * It can be rendered by appropriate Renderer in Admin Pages
 */
class AitOptionsControlsGroup
{

	protected $id = null;
	protected $index = null;
	protected $reset = false;
	protected $import = false;
	protected $disabled = false;
	protected $configuration = null;
	protected $configName = null;
	protected $used = false;
	protected $advancedEnabled = false;

	/** @var AitOptionsControlsSection[] */
	protected $sections = array();


	public function setId($id)
	{
		$this->id = $id;
	}


	public function getId()
	{
		return $this->id;
	}


	public function setIndex($index)
	{
		$this->index = $index;
	}


	public function getIndex()
	{
		return $this->index;
	}


	public function setReset($reset)
	{
		$this->reset = $reset;
	}



	public function getReset()
	{
		return $this->reset;
	}



	public function getImport()
	{
		return $this->import;
	}



	public function setImport($import)
	{
		$this->import = $import;
	}



	public function getDisabled()
	{
		return $this->disabled;
	}



	public function setDisabled($disabled)
	{
		$this->disabled = $disabled;
	}



	public function getConfiguration()
	{
		return $this->configuration;
	}



	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
	}



	public function setConfigName($configName)
	{
		$this->configName = $configName;
	}



	public function getConfigName()
	{
		return $this->configName;
	}



	public function getUsed()
	{
		return $this->used;
	}



	public function setUsed($used)
	{
		$this->used = $used;
	}



	public function setAdvancedEnabled($advancedEnabled)
	{
		$this->advancedEnabled = $advancedEnabled;
	}




	public function areAdvancedEnabled()
	{
		return $this->advancedEnabled;
	}



	public function addSection(AitOptionsControlsSection $section)
	{
		$this->sections[] = $section;
	}



	public function getSections()
	{
		return $this->sections;
	}

}
