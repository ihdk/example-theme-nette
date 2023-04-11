<?php


return array(
	'page-title' => array(
		'title' => _x('Page Title', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => true,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'sortable' => false,
			'cloneable' => false,
			'columnable' => false,
		),
	),

	'revolution-slider' => array(
		'title' => _x('Revolution Slider', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'sortable' => false,
			'cloneable' => false,
			'columnable' => false,
		),
	),

	'content' => array(
		'title' => _x('Content', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => true,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => false,
			'columnable' => false,
			'sortable' => true,
		),
	),

	'comments' => array(
		'title' => _x('Comments', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => true,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => false,
			'sortable' => true,
			'columnable' => false,
		),
		'icon' => 'fa-comments-o',
	),

	'portfolio' => array(
		'title' => _x('Portfolio', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'columnable' => false,
			'sortable' => true,
			'cloneable' => true,
			'cpt' => array(
				'portfolio-item',
			),
			'assets' => array(
				'js' => array(
					'jquery-quicksand' => array(
						'file' => '/libs/jquery.quicksand.js',
						'deps' => array('ait'),
					),
					'jquery-quicksand-sorting' => array(
						'file' => '/libs/jquery.quicksand.sorting-1.3.js',
						'deps' => array('ait'),
					),
					'jquery-easing' => array(
						'file' => '/libs/jquery.easing-1.3.js',
						'deps' => array('ait'),
					),
					'ait-jquery-portfolio' => array(
						'file' => '/jquery.portfolio.js',
						'deps' => array(
							'ait',
							'jquery-quicksand',
							'jquery-quicksand-sorting',
							'jquery-easing',
							'jquery-colorbox',
						),
						'in-footer' => true,
					),
				),
			),
		),
		'icon' => 'fa-camera',
		'color' => '#6FBAB2',
	),

	'testimonials' => array(
		'title' => _x('Testimonials', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'testimonial',
			),
			'assets' => array(
				'js' => array(
					'ait-jquery-carousel' => true,
				),
			),
		),
		'icon' => 'fa-comments-o',
		'color' => '#898f77',
	),

	'text' => array(
		'title' => _x('Text', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => true,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-align-left',
		'color' => '#d6ab2f',
	),

	'columns' => array(
		'free' => true,
		'title' => _x('Columns', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => true,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => false,
            'narrow-columns' => array(
                'column-grid-2' => array(
                    'column-span-1',
                ),
                'column-grid-3' => array(
                    'column-span-1',
                ),
                'column-grid-4' => array(
                    'column-span-1',
                    'column-span-2',
                ),
                'column-grid-5' => array(
                    'column-span-1',
                    'column-span-2',
                ),
                'column-grid-6' => array(
                    'column-span-1',
                    'column-span-2',
                    'column-span-3',
                ),
            )
		),
		'icon' => 'fa-columns',
	),

	'partners' => array(
		'title' => _x('Partners', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'partner',
			),
		),
		'icon' => 'fa-heart',
		'color' => '#e27b86',
	),

	'facebook' => array(
		'title' => _x('Facebook', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-facebook-official',
		'color' => '#405a9a',
	),

	'faq' => array(
		'free' => false,
		'title' => _x('FAQ', 'name of element', 'ait-admin'),
		'package' => array(
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'faq'
			),
		),
		'icon' => 'fa-question-circle',
		'color' => '#689ae3',
	),

	'google-map' => array(
		'title' => _x('Google Map', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'assets' => array(
				'js' => array(
					'jquery-gmap3' => true,
					'modernizr' => true,
				),
			),
		),
		'icon' => 'fa-map-marker',
		'color' => '#93dd8b',
	),

	'member' => array(
		'title' => _x('Member', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'member',
			),
		),
		'icon' => 'fa-user',
		'color' => '#915db1',
	),

	'price-table' => array(
		'title' => _x('Price Table', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => false,
			'cpt' => array(
				'price-table',
			),
			'assets' => array(
				'js' => array(
					'ait-jquery-pricetable' => array(
						'file' => '/jquery.pricetable.js',
						'deps' => array('ait'),
						'in-footer' => true,
					),
				),
			),
		),
		'icon' => 'fa-dollar',
		'color' => '#a99b63',
	),

	'twitter' => array(
		'title' => _x('Twitter', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-twitter',
		'color' => '#3cadf0',
	),

	'video' => array(
		'title' => _x('Video', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-play-circle',
		'color' => '#d45857',
	),

	'toggles' => array(
		'title' => _x('Toggles', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'toggle',
			),
			'assets' => array(
				'js' => array(
					'jquery-ui-tabs' => true,
					'jquery-ui-accordion' => true,
					'ait-tabs-script' => array(
						'file' => '/tabs.js',
						'deps' => array('jquery', 'ait-mobile-script'),
					),
				),
			),
		),
		'icon' => 'fa-caret-square-o-down',
		'color' => '#69bf30',
	),

 	'soundcloud' => array(
		'title' => _x('SoundCloud', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-soundcloud',
		'color' => '#F98700',
	),

	'mixcloud' => array(
		'title' => _x('Mixcloud', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-mixcloud',
		'color' => '#7E7E7E',
	),

	'counters' => array(
		'title' => _x('Counters', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => true,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'assets' => array(
				'js' => array(
					'ait-jquery-charts' => array(
						'file' => '/jquery.charts.js',
						'deps' => array('ait'),
					),
				),
			),
		),
		'icon' => 'fa-rotate-right',
		'color' => '#A8CA3A',
	),

	'countdown' => array(
		'title' => _x('Countdown', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'assets' => array(
				'js' => array(
					'ait-countdown' => array(
						'file' => '/jquery.countdown.js',
						'deps' => array('ait'),
					),
				),
			),
		),
		'icon' => 'fa-history',
		'color' => '#DCB828',
	),

	'rule' => array(
		'title' => _x('Horizontal Rule', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => false,
			'assets' => array(
				'js' => array(
					'ait-rule-btn' => array(
						'file' => '/rule-btn.js',
						'deps' => array('ait'),
					)
				),
			),
		),
		'icon' => 'fa-minus-square',
		'color' => '#9f806d',
	),

	'events' => array(
		'title' => _x('Events', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'event',
			),
			'assets' => array(
				'js' => array(
					'ait-jquery-carousel' => true,
				),
			),
		),
		'icon' => 'fa-calendar',
		'color' => '#ECA42A',
	),

	'events-with-map' => array(
		'title' => _x('Events With Map', 'name of element', 'ait-admin'),
		'disabled' => true, // disabled by default, only enabled explicitly in some themes like cityguide
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'event-with-map',
			),
			'assets' => array(
				'js' => array(
					'ait-jquery-carousel' => true,
				),
			),
		),
	),

	'job-offers' => array(
		'title' => _x('Job Offers', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'job-offer',
			),
			'assets' => array(
				'js' => array(
					'ait-jquery-carousel' => true,
				),
			),
		),
		'icon' => 'fa-briefcase',
		'color' => '#9bbb63',
	),

	'opening-hours' => array(
		'title' => _x('Opening Hours', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-clock-o',
		'color' => '#6E80DA',
	),

	'contact-form' => array(
		'title' => _x('Contact Form', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'assets' => array(
				'js' => array(
					'jquery-ui-datepicker' => true
				),
			),
		),
		'icon' => 'fa-envelope-square',
		'color' => '#96a337',
	),

	'sitemap' => array(
		'title' => _x('Sitemap', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-sitemap',
		'color' => '#6DDAB2',
	),

	'advertising-spaces' => array(
		'title' => _x('Advertisements', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'ad-space',
			),
			'assets' => array(
				'js' => array(
					'ait-adspaces' => array(
						'file' => '/jquery.adspaces.js',
						'deps' => array('ait'),
					),
				),
			),
		),
		'icon' => 'fa-laptop',
		'color' => '#c340d1',
	),

	'seo' => array(
		'title' => _x('SEO', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'business' => true,
			'developer' => true,
			'themeforest' => true,
		),
		'configuration' => array(
			'sortable' => false,
			'cloneable' => false,
			'columnable' => false,
		),
	),

	'posts' => array(
		'title' => _x('Posts', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'assets' => array(
				'js' => array(
					'ait-jquery-carousel' => true,
					'transit'	=> true,
				),
			),
		),
		'icon' => 'fa-newspaper-o',
		'color' => '#50a4dc',
	),

	'members' => array(
		'title' => _x('Members', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'member',
			),
			'assets' => array(
				'js' => array(
					'ait-jquery-carousel' => true,
					'transit'	=> true
				),
			),
		),
		'icon' => 'fa-users',
		'color' => '#aa5d8b',
	),

	'services' => array(
		'title' => _x('Services', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => false,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'service-box',
			),
			'assets' => array(
				'js' => array(
					'ait-jquery-carousel' => true,
					'transit' => true
				),
			),
		),
		'icon' => 'fa-cog',
		'color' => '#7D5BE2',
	),

	'easy-slider' => array(
		'title' => _x('Easy Slider', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'assets' => array(
				'css' => array(
					'jquery-bxslider' => true,
				),
				'js' => array(
					'jquery-bxslider' => true,
				),
			),
		),
		'icon' => 'fa-clone',
		'color' => '#8cc7e9',
	),

	'products' => array(
		'title' => _x('Products', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
			'cpt' => array(
				'product-item',
			),
		),
		'icon' => 'fa-briefcase',
		'color' => '#C49800',
	),

	'image' => array(
		'title' => _x('Image', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-photo',
	),

	'lists' => array(
		'title' => _x('Lists', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-list-ul',
	),

	'promotion' => array(
		'title' => _x('Promotion', 'name of element', 'ait-admin'),
		'package' => array(
			'free' => false,
			'standard' => true,
			'themeforest' => true,
			'business' => true,
			'developer' => true,
		),
		'configuration' => array(
			'cloneable' => true,
			'sortable' => true,
			'columnable' => true,
		),
		'icon' => 'fa-columns',
		'color' => '#1F8998',
	),
);
