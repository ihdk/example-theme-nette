<?php


return array(
	'css' => array(
		'jquery-colorbox' => array(
			'file'    => aitUrl('assets', '/colorbox/colorbox.min.css'),
			'ver'     => '1.4.27',
			'enqueue' => true,
		),
		'jquery-ui-css' => array(
			'file'    => aitUrl('assets', '/jquery-ui-css/jquery-ui.css'),
			'ver'     => '1.8.24',
			'enqueue' => false,
		),
		'jquery-bxslider' => array(
			'file'    => aitUrl('assets', '/bxslider/jquery.bxslider.css'),
			'ver'     => '4.1.2',
			'enqueue' => false,
		),
		'element-placeholder' => array(
			'file'    => aitUrl('assets', '/ait/element-placeholder.css'),
			'ver'     => AIT_THEME_VERSION,
			'enqueue' => true,
		),
		'ait-leaflet' => array(
			'file'  => aitUrl('assets', '/leaflet/leaflet.css'),
			'deps'	=> array(),
		),
	),

	'js' => array(
		'ait' => array(
			'file'      => aitUrl('assets', '/ait/ait.js'),
			'deps'      => array('jquery', 'underscore'),
			'ver'       => AIT_THEME_VERSION,
			'in-footer' => true,
			'enqueue'   => true,
		),

		'ait-jquery-carousel' => array(
			'file'      => aitUrl('assets', '/ait-jquery-carousel/jquery.carousel.js'),
			'deps'      => array('ait'),
			'enqueue'   => false,
			'in-footer' => true,
		),

		'jquery-colorbox' => array(
			'file'      => aitUrl('assets', '/colorbox/jquery.colorbox.min.js'),
			'deps'      => array(),
			'ver'       => '1.4.27',
			'in-footer' => true,
			'enqueue'   => true,
		),

		'googlemaps-api' => array(
			'file'    => 'https://maps.google.com/maps/api/js?language={gmaps-lang}&key={gmaps-api-key}',
			'enqueue' => false,
			'lang'    => true,
			'api-key' => true,
		),

		'jquery-gmap3' => array(
			'file'      => aitUrl('assets', '/gmap3/gmap3.min.js'),
			'deps'      => array('googlemaps-api'),
			'enqueue'   => false,
			'in-footer' => true,
			'ver'       => '5.0b',
		),

		'ait-leaflet' => array(
			'file'  => aitUrl('assets', '/leaflet/leaflet.js'),
			'deps'	=> array(),
		),

		'modernizr' => array(
			'file'      => aitUrl('assets', '/modernizr/modernizr.touch.js'),
			'deps'      => array(),
			'enqueue'   => false,
			'in-footer' => true,
			'ver'       => '2.6.2',
		),

		'placeholders' => array(
			'file'      => aitUrl('assets', '/placeholders/placeholders.min.js'),
			'deps'      => array(),
			'enqueue'   => false,
			'in-footer' => true,
			'ver'       => '2.1.0',
		),

		'transit'	=> array(
			'file'      => aitUrl('assets', '/transit/jquery.transit-0.9.9.min.js'),
			'deps'      => array(),
			'enqueue'   => false,
			'in-footer' => true,
			'ver'       => '0.9.9',
		),

		'datepicker-translation' => array(
			// datepicker translations are used in admin (= framework) and in frontend - until better file structure is made, keep them in admin assets
			'file'		=> aitPaths()->url->admin . "/assets/libs/datepicker/jquery-ui-i18n.min.js",
			'deps'		=> array('jquery-ui-datepicker'),
			'enqueue-only-if' => function() { return AitLangs::getCurrentLanguageCode() != "en"; },
		),

		'jquery-bxslider' => array(
			'file'      => aitUrl('assets', '/bxslider/jquery.bxslider.min.js'),
			'deps'      => array('ait'),
			'ver'       => '4.1.2',
			'in-footer' => true,
			'enqueue'   => false,
		),

	),

);
