<?php


class AitAutomaticPluginInstallation
{

	public static function run()
	{
		$plugins = self::getPrepackedPluginsPaths();

		$installer = new AitPluginBulkInstaller(new Automatic_Upgrader_Skin());

		$installer->bulkInstall($plugins);
	}



	protected static function getPrepackedPluginsPaths()
	{
		$packages = array();
		$plugins = AitTheme::getConfiguration('plugins');
		$paidPlugins = AitTheme::getConfiguration('paid-plugins');
		if(!empty($paidPlugins) and AIT_THEME_PACKAGE !== 'basic'){
			$plugins = array_merge($plugins, $paidPlugins);
		}

		foreach($plugins as $slug => $plugin){
			if(
				isset($plugin['source']) and file_exists($plugin['source']) and
				isset($plugin['ait-auto-install']) and $plugin['ait-auto-install'] === true // this how plugins pre-packed by AIT are marked in plugins.php config
			){
				$packages[] = $plugin['source'];
			}
		}

		return $packages;
	}
}
