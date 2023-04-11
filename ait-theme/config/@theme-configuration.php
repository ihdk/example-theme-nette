<?php

/*
 * AIT WordPress Theme
 *
 * Copyright (c) 2014, Affinity Information Technology, s.r.o. (http://ait-themes.com)
 */

return array(

	'menus' => array(
		'main'   => __('Main menu', 'ait-admin'),
		'footer' => __('Footer menu', 'ait-admin'),
	),

	// Supported standard WordPress features
	'theme-support' => array(
		'woocommerce',
		'automatic-feed-links',
		'post-thumbnails',
	),

	// Supported custom ait-theme features
	'ait-theme-support' => array(
        'ait-languages-plugin',
		'megamenu',
		'cpts' => array(
			'ad-space',
			'event',
			'faq',
			'job-offer',
			'member',
			'partner',
			'portfolio-item',
			'price-table',
			'service-box',
			'testimonial',
			'toggle',
			'item',
		),
		'elements' => array(
			'ait-item-extension',
			'events-pro',
			'claim-listing',
			'get-directions',
			'advertising-spaces',
			'contact-form',
			'countdown',
			'columns',
			'counters',
			'easy-slider',
			'events',
			'facebook',
			'faq',
			'featured-items',
			'google-map',
			'header-map',
			'header-video',
			'items',
			'items-info',
			'job-offers',
			'member',
			'members',
			'mixcloud',
			'opening-hours',
			'page-title',
			'partners',
			'portfolio',
			'posts',
			'price-table',
			'revolution-slider',
			'rule',
			'seo',
			'services',
			'search-form',
			'sitemap',
			'soundcloud',
			'taxonomy-list',
			'testimonials',
			'text',
			'toggles',
			'twitter',
			'video',
			'widget-area',
			'promotion',
		),
	),

	'plugins' => array(
		'ait-toolkit' => array(
			'required' => true,
			'name'     => 'AIT Elements Toolkit',
		),
		'ait-shortcodes' => array(
			'required' => true,
			'name'     => 'AIT Shortcodes',
		),
		'revslider' => array(
			'required' => true,
		),
	),

	'assets' => array(
		'fonts' => array(
			'awesome',
			'roboto' => array(
				'bold', 'italic', 'regular',
			),
			'robotoslab' => array(
				'bold', 'regular',
			),
			'opensans' => array(
				'bold', 'italic', 'regular', 'condbold'
			),
		),

		'css' => array(
			'jquery-selectbox' => array(
				'file' => '/libs/jquery.selectbox.css',
			),
			'jquery-select2' => array(
				'file' => '/libs/jquery.select2-3.5.1.css',
			),
			'font-awesome'	=> array(
				'file'	=> '/libs/font-awesome.css',
			),
			'ait-leaflet'	=> array(
				'file'	=> '/libs/leaflet/leaflet.css',
				'deps' 	=> array(),
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'openstreetmap';
				},
			),
			'ait-leaflet-markercluster'	=> array(
				'file'	=> '/libs/leaflet/MarkerCluster.css',
				'deps' => array('ait-leaflet-markercluster-default'),
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'openstreetmap';
				},
			),
			'ait-leaflet-markercluster-default'	=> array(
				'file'	=> '/libs/leaflet/MarkerCluster.Default.css',
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'openstreetmap';
				},
			),
			'ait-leaflet-gesture-handling' => array(
				'file'	=> '/libs/leaflet/leaflet-gesture-handling.min.css',
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'openstreetmap';
				},
			),
			'jquery-ui-css' => true,
		),
		'js' => array(
			'jquery-selectbox' => array(
				'file' => '/libs/jquery.selectbox-0.2.js',
				'deps' => array('jquery')
			),
			'jquery-select2' => array(
				'file' => '/libs/jquery.select2-3.5.1.js',
				'deps' => array('jquery')
			),
			'jquery-raty' => array(
				'file' => '/libs/jquery.raty-2.5.2.js',
				'deps' => array('jquery')
			),
			'jquery-waypoints' => array(
				'file' => '/libs/jquery-waypoints-2.0.3.js',
				'deps' => array('jquery')
			),
			'jquery-infieldlabels' => array(
				'file'	=> '/libs/jquery.infieldlabel-0.1.4.js',
				'deps'	=> array('jquery'),
			),
			'jquery-gmap3-local' => array(
				'file'	=> '/libs/gmap3.min.js',
				'deps'	=> array('jquery', 'googlemaps-api'),
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'google';
				},
			),
			'jquery-gmap3-infobox-local' => array(
				'file'	=> '/libs/gmap3.infobox.js',
				'deps'	=> array('jquery', 'jquery-gmap3-local'),
				'enqueue'   => false,
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'google';
				},
			),
			'ait-leaflet' => array(
				'file'	=> '/libs/leaflet/leaflet.js',
				'deps'	=> array(),
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'openstreetmap';
				},
			),
			'ait-leaflet-markercluster' => array(
				'file'	=> '/libs/leaflet/leaflet.markercluster.js',
				'deps'	=> array('ait-leaflet'),
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'openstreetmap';
				},
			),
			'ait-leaflet-gesture-handling' => array(
				'file'	=> '/libs/leaflet/leaflet-gesture-handling.min.js',
				'deps'	=> array('ait-leaflet'),
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'openstreetmap';
				},
			),
			/* AIT CUSTOM SCRIPTS */
			'ait-mobile-script' => array(
				'file' => '/mobile.js',
				'deps' => array('jquery')
			),
			'ait-menu-script' => array(
				'file' => '/menu.js',
				'deps' => array('jquery', 'ait-mobile-script')
			),
			'ait-portfolio-script' => array(
				'file' => '/portfolio-item.js',
				'deps' => array('jquery', 'ait-mobile-script', 'jquery-ui-accordion', 'jquery-bxslider')
			),
			'ait-custom-script' => array(
				'file' => '/custom.js',
				'deps' => array('jquery', 'ait-mobile-script')
			),
			'ait-woocommerce-script' => array(
				'file' => '/woocommerce.js',
				'deps' => array('jquery'),
				'enqueue-only-if' => function() { return !is_admin() and aitIsPluginActive("woocommerce"); },
			),
			'marker-clusterer' => array(
				'file'      => aitUrl('assets', '/marker-clusterer/markerclusterer-plus.js'),
				'deps'      => array('googlemaps-api', 'ait'),
				'enqueue-only-if' => function() { 
					return getMapProvider() == 'google';
				},
				'ver'       => '2.1.1',
			),
			/* AIT CUSTOM SCRIPTS */
			'ait-script' => array(
				'file' => '/script.js',
				'deps' => array('jquery', 'ait-mobile-script', 'ait-menu-script', 'ait-portfolio-script', 'ait-custom-script')
			),
			'modernizr' => true,
		),
	),

	'frontend-ajax' => array(
		'send-email',
		'contact-owner',
		'login-widget-check-captcha',
		'get-items',
		'contact-owner-captcha',
	),
);