<?php

/**
 * How to Use:
 * Pointers are defined in an associative array and passed to the class upon instantiation.
 * First we hook into the 'admin_enqueue_scripts' hook with our function:
 *
 *   add_action('admin_enqueue_scripts', 'myHelpPointers');
 *
 *   function myHelpPointers() {
 *      //First we define our pointers
 *      $pointers = array(
 *                       array(
 *                           'id' => 'xyz123',   // unique id for this pointer
 *                           'screen' => 'page', // this is the page hook we want our pointer to show on
 *                           'target' => '#element-selector', // the css selector for the pointer to be tied to, best to use ID's
 *                           'title' => 'My ToolTip',
 *                           'content' => 'My tooltips Description',
 *                           'position' => array(
 *                                              'edge' => 'top', //top, bottom, left, right
 *                                              'align' => 'middle' //top, bottom, left, right, middle
 *                                              )
 *                           )
 *                        // more as needed
 *                        );
 *      //Now we instantiate the class and pass our pointer array to the constructor
 *      //Set second boolean parameter to true for step-by-step tutorial
 *      $myPointers = new WP_Help_Pointer($pointers, bool);
 *    }
 *
 * EDITED BY AIT
 * Ability to make pointers as a step-by-step tutorial
 *
 * @package WP_Help_Pointer
 * @version 0.1
 * @author Tim Debo <tim@rawcreativestudios.com>
 * @copyright Copyright (c) 2012, Raw Creative Studios
 * @link https://github.com/rawcreative/wp-help-pointers
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

class WP_Help_Pointer {

    public $screen_id;
    public $valid;
    public $pointers;
	public $steps;

    public function __construct( $pntrs = array(), $steps = null ) {

        // Don't run on WP < 3.3
        if ( get_bloginfo( 'version' ) < '3.3' )
            return;

        $screen = get_current_screen();
        $this->screen_id = $screen->id;
		$this->steps = $steps;

        $this->register_pointers($pntrs);

        add_action( 'admin_enqueue_scripts', array( $this, 'add_pointers' ), 1000 );
        add_action( 'admin_head', array( &$this, 'add_scripts' ) );
    }

    public function register_pointers( $pntrs ) {

		$pointers = null;

        foreach( $pntrs as $ptr ) {

            if( $ptr['screen'] == $this->screen_id ) {

                $pointers[$ptr['id']] = array(
                    'screen' => $ptr['screen'],
                    'target' => $ptr['target'],
                    'options' => array(
                        'content' => sprintf( '<h3> %s </h3> <p> %s </p>',$ptr['title'] , $ptr['content'] ),
                        'position' => $ptr['position']
                    )
                );

            }
        }

         $this->pointers = $pointers;

    }

    public function add_pointers() {

        $pointers = $this->pointers;

        if ( ! $pointers || ! is_array( $pointers ) )
            return;

        // Get dismissed pointers
        $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
        $valid_pointers = array();

        // Check pointers and remove dismissed ones.
        foreach ( $pointers as $pointer_id => $pointer ) {

            // Make sure we have pointers & check if they have been dismissed
            if ( in_array( $pointer_id, $dismissed ) || empty( $pointer )  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) )
                continue;

            $pointer['pointer_id'] = $pointer_id;

            // Add the pointer to $valid_pointers array
            $valid_pointers['pointers'][] =  $pointer;
        }

        // No valid pointers? Stop here.
        if ( empty( $valid_pointers ) )
            return;

        $this->valid = $valid_pointers;

        wp_enqueue_style( 'wp-pointer' );
        wp_enqueue_script( 'wp-pointer' );
    }

    public function add_scripts() {
        $pointers = $this->valid;

        if( empty( $pointers ) )
            return;

        $pointers = json_encode( $pointers );
		$steps = $this->steps ? 'true' : 'false';
		$prev = __('Prev', 'ait-admin');
		$next = __('Next', 'ait-admin');
		$done = __('Done', 'ait-admin');

        echo <<<HTML
        <script>
        jQuery(document).ready( function($) {
            var WPHelpPointer = {$pointers};
			var pointerIDs = [];
			var pointerAligns = [];

            $.each(WPHelpPointer.pointers, function(i) {
                wp_help_pointer_init(i);
            });

            function wp_help_pointer_init(i) {
                pointer = WPHelpPointer.pointers[i];
				if (!{$steps}) {
					options = $.extend( pointer.options, {
						close: function() {
							$.post( ajaxurl, {
								pointer: pointer.pointer_id,
								action: 'dismiss-wp-pointer'
							});
						}
					});
				} else {
					options = pointer.options;
				}
				pointerIDs.push(pointer.pointer_id);
				pointerAligns.push(pointer.options.position.align);
                $(pointer.target).pointer( options ).pointer('open');
            }

			if ({$steps}) {

				$('.wp-pointer').wrapAll("<div class='wp-pointers' />");

				var pointers = $('.wp-pointers .wp-pointer');
				var	count = pointers.length;
				var	dismiss = function() {
					$('.wp-pointers .open').removeClass('open');
					ait.admin.ajax.post("dismissPointers", {
						'pointer': pointerIDs
					});
				}

				pointers.each(function(i) {
					/* Aligns */
					$(this).addClass('wp-pointer-align-'+ pointerAligns[i]);

					/* Counters */
					var title = $(this).find('h3');
					title.append($('<span class="counter">'+ (i+1) +' / '+ count +'</span>'));

					/* Buttons */
					var buttons = $(this).find('.wp-pointer-buttons');
					if (i != (count - 1)) buttons.append($('<a class="next button button-primary" href="#">{$next}</a>'));
					if (i == (count - 1)) buttons.append($('<a class="done button button-primary" href="#">{$done}</a>'));
					if (i != 0) buttons.append($('<a class="prev button button-secondary" href="#">{$prev}</a>'));
				});

				$('.wp-pointers .next').on('click', function() {
					var pointer = $(this).parents('.wp-pointer');
					var pointerOffset = parseInt(pointer.next().css('top'), 10) - ( $(window).height() - pointer.next().outerHeight(true) ) / 2;
					$(this).parents('.wp-pointer')
						.removeClass('open')
						.next().addClass('open');
					if (!isElementInViewport(pointer.next())) {
						$('html, body').animate({
							scrollTop: pointerOffset
						}, 1000);
					}

				});

				$('.wp-pointers .prev').on('click', function() {
					var pointer = $(this).parents('.wp-pointer');
					var pointerOffset = parseInt(pointer.prev().css('top'), 10) - ( $(window).height() - pointer.prev().outerHeight(true) ) / 2;
					$(this).parents('.wp-pointer')
						.removeClass('open')
						.prev().addClass('open');
					if (!isElementInViewport(pointer.prev())) {
						$('html, body').animate({
							scrollTop: pointerOffset
						}, 1000);
					}
				});

				$('.wp-pointers .close').on('click', dismiss);
				$('.wp-pointers .done').on('click', dismiss);

				$('.wp-pointers .wp-pointer:first-child').addClass('open');

				function isElementInViewport (el) {
					if (typeof jQuery === "function" && el instanceof jQuery) {
						el = el[0];
					}

					var rect = el.getBoundingClientRect();

					return (
						rect.top >= 0 &&
						rect.left >= 0 &&
						rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
						rect.right <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
					);
				}

			}
        });
        </script>
HTML;

    }

} // end class
