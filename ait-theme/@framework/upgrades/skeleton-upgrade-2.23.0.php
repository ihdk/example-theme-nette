<?php


class AitSkeletonUpgrade2230
{

	protected $errors = array();



	public function execute()
	{
		$this->updateUpdater();

		return $this->errors;
	}



	protected function updateUpdater()
	{
		if(@is_writable(WP_PLUGIN_DIR)){
			AitAutomaticPluginInstallation::run();
		}
	}

}
