<?php

return array(
	'ait-updater' => array(
		'name'             => 'AIT Updater',
		'version'          => '3.4.1',
		'required'         => true,
		'source'           => aitPath('plugins', '/ait-updater.zip'),
		'ait-auto-install' => true,
		'ait-packages'     => array('basic', 'premium'),
	),
	'ait-sysinfo' => array(
		'name'             => 'AIT SysInfo',
		'version'          => '1.0.1',
		'required'         => false,
		'source'           => aitPath('plugins', '/ait-sysinfo.zip'),
		'ait-auto-install' => false,
		'ait-packages'     => array('basic', 'premium', 'themeforest'),
	),
	'ait-toolkit' => array(
		'name'             => 'AIT Elements Toolkit',
		'version'          => '2.0.0',
		'required'         => true,
		'source'           => aitPath('plugins', '/ait-toolkit.zip'),
		'ait-auto-install' => true,
		'ait-packages'     => array('premium', 'themeforest'),
	),
	'ait-shortcodes' => array(
		'name'             => 'AIT Shortcodes',
		'version'          => '1.1.1',
		'required'         => true,
		'source'           => aitPath('plugins', '/ait-shortcodes.zip'),
		'ait-auto-install' => true,
		'ait-packages'     => array('premium', 'themeforest'),
	),
	'revslider' => array(
		'name'             => 'Revolution Slider',
		'version'          => '5.2.6',
		'required'         => false,
		'source'           => aitPath('plugins', '/revslider.zip'),
		'ait-auto-install' => true,
		'ait-packages'     => array('premium', 'themeforest'),
	),
);
