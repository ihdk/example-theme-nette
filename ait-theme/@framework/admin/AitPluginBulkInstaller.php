<?php

if(!class_exists('Plugin_Upgrader', false)){
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

class AitPluginBulkInstaller extends Plugin_Upgrader
{

	public $result;

	public $bulk = true;

	protected $clear_destination = false;



	public function __construct($skin)
	{
		parent::__construct($skin);

	}



	public function run( $options )
	{
		$result = parent::run( $options );
		$this->install_strings();

		return $result;
	}



	public function bulkInstall($plugins, $args = array())
	{
		add_filter( 'upgrader_post_install', array( $this, 'autoActivate' ), 10 );

		$defaults    = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->bulk = true;

		$results = array();

		$this->update_count   = count( $plugins );
		$this->update_current = 0;
		foreach ( $plugins as $plugin ) {
			$this->update_current++;

			$result = $this->run(
				array(
					'package'           => $plugin,
					'abort_if_destination_exists' => !AitUtils::contains($plugin, 'ait-updater'), // always update AIT Updater on theme activation.
					'destination'       => WP_PLUGIN_DIR,
					'clear_destination' => false,
					'clear_working'     => true,
					'is_multi'          => true,
					'hook_extra'        => array(
						'plugin' => $plugin,
					),
				)
			);

			$results[ $plugin ] = $this->result;

			// Prevent credentials auth screen from displaying multiple times.
			if ( false === $result ) {
				break;
			}
		}

		do_action( 'upgrader_process_complete', $this, array(
			'action'  => 'install',
			'type'    => 'plugin',
			'bulk'    => true,
			'plugins' => $plugins,
		) );

		remove_filter( 'upgrader_post_install', array( $this, 'autoActivate' ), 10 );

		// Force refresh of plugin update information.
		wp_clean_plugins_cache( $parsed_args['clear_update_cache'] );

		return $results;
	}



	public function autoActivate($bool)
	{
		wp_clean_plugins_cache();

		$pluginInfo = $this->plugin_info();

		if(!is_plugin_active($pluginInfo)){
			$activate = activate_plugin($pluginInfo);
		}

		return $bool;
	}
}
