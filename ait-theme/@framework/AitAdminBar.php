<?php


class AitAdminBar
{

	public static function register()
	{
		add_action("wp_ajax_toggleDevMode", array(__CLASS__, "ajaxToggleDevMode"));
		add_action("wp_ajax_emptyThemeCacheDir", array(__CLASS__, "ajaxEmptyThemeCacheDir"));
		add_action("wp_ajax_emptyWPThumbCacheDir", array(__CLASS__, "ajaxEmptyWPThumbCacheDir"));

		add_action('admin_bar_menu', array(__CLASS__, 'generateThemeAdminMenu'), 31);
		add_action('admin_bar_menu', array(__CLASS__, 'generateDevMenu'), 1999);
		// add_action('admin_bar_menu', array(__CLASS__, 'enhanceDefaultBar'), 21);

		add_action('admin_bar_menu', array(__CLASS__, 'addPageBuilderLink'), 100);


		add_action('admin_head', array(__CLASS__, 'cssAndJs'));
		add_action('wp_head', array(__CLASS__, 'cssAndJs'));
	}



	public static function generateThemeAdminMenu($wp_admin_bar)
	{
		$adminMenuItems = aitConfig()->getAdminConfig('pages');

		if(!current_user_can(apply_filters('ait-admin-bar-permission', 'manage_options'))) return;

		__('Theme Admin', 'ait');  // just for adminbar on frontend

		if(!empty($adminMenuItems)){
			$wp_admin_bar->add_node(array(
					'id'    => 'ait-admin-menu',
					'title' => '<span class="ab-icon"></span><span class="ab-label">' . __('Theme Admin', 'ait-admin') . '</span>',
					'href'  => '#',
					'meta'  => array('class' => 'ait-admin-menu'),
				));

			$t = aitConfig()->getFullConfig('theme');


			foreach($adminMenuItems as $i => $item){
				if($item['slug'] != 'admin'){

					$wp_admin_bar->add_node(array(
							'id'     => 'ait-' . $item['slug'],
							'title'  => $item['menu-title'],
							'href'   => AitUtils::adminPageUrl(array('page' => $item['slug'])),
							'parent' => 'ait-admin-menu',
						));


					if($item['slug'] == 'theme-options'){
						foreach($t as $groupKey => $groupData){

							$title = (!empty($groupData['@title'])) ? $groupData['@title'] : $groupKey;
							$id = sanitize_key(sprintf("ait-%s-%s-panel", $item['slug'], $groupKey));
							$_translate = '__';
							$wp_admin_bar->add_node(array(
									'id'     => $id,
									'title'  => $_translate($title, 'ait-admin'),
									'href'   => AitUtils::adminPageUrl(array('page' => $item['slug'])) . '#' . $id,
									'parent' => 'ait-' . $item['slug'],
								));
						}
					}


					if(isset($item['sub'])){
						foreach($item['sub'] as $j => $subItem){
							$wp_admin_bar->add_node(array(
									'id'     => 'ait-' . $subItem['slug'],
									'title'  => $subItem['menu-title'],
									'href'   => AitUtils::adminPageUrl(array('page' => $subItem['slug'])),
									'parent' => 'ait-admin-menu',
								));
						}
					}

				}
			}
		}
	}



	public static function generateDevMenu($wp_admin_bar)
	{
		if(!defined('AIT_SERVER')){
			return;
		}

		$title = __('Dev mode: ', 'ait-admin');

		if(AIT_DEV){
			$l = 'on';
			$s = 'state-on';
		}else{
			$l = 'off';
			$s = '';
		}

		$title .= sprintf("<strong class='ait-dev-mode-state %s'>%s</strong>", $s, $l);

		$wp_admin_bar->add_node(array(
				'id' => 'ait-dev-mode',
				'title'  => $title,
				'parent' => 'top-secondary', // Off on the right side
				'href' => '#',
				'meta' => array('class' => 'ait-dev-mode'),
			));

		$wp_admin_bar->add_node(array(
				'id' => 'ait-empty-theme-cache',
				'title'  => __('Empty theme cache', 'ait-admin'),
				'parent' => 'ait-dev-mode',
				'href' => '#',
			));

		$wp_admin_bar->add_node(array(
				'id' => 'ait-empty-wpthumb-cache',
				'title'  => __('Empty image (WPThumb) cache', 'ait-admin'),
				'parent' => 'ait-dev-mode',
				'href' => '#',
			));

		$wp_admin_bar->add_node(array(
				'id' => 'ait-current-site-id',
				'title'  => 'Site ID: ' . get_current_blog_id(),
				'parent' => 'ait-dev-mode',
				'href' => '#',
			));
	}



	public static function enhanceDefaultBar($wp_admin_bar)
	{
		if(is_multisite()){
			// My Sites -> Network Admin
			$wp_admin_bar->add_menu(array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-p',
					'title'  => __('Plugins', 'default'),
					'href'   => network_admin_url('plugins.php'),
				));

			$wp_admin_bar->add_menu(array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-t',
					'title'  => __('Themes', 'default'),
					'href'   => network_admin_url('themes.php'),
				));

			// Individual sites' menus
			$adminMenuItems = aitConfig()->getAdminConfig('pages');

			foreach((array) $wp_admin_bar->user->blogs as $blog){
				switch_to_blog($blog->userblog_id);

				$menuId  = 'blog-' . $blog->userblog_id;

				if(current_user_can('switch_themes')){
					$wp_admin_bar->add_menu( array(
							'parent' => $menuId,
							'id'     => $menuId . '-t',
							'title'  => __('Themes', 'default'),
							'href'   => admin_url('themes.php'),
						));
				}

				if(current_user_can('activate_plugins')){
					$wp_admin_bar->add_menu( array(
							'parent' => $menuId,
							'id'     => $menuId . '-p',
							'title'  => __('Plugins', 'default'),
							'href'   => admin_url('plugins.php'),
						));
				}


				foreach($adminMenuItems as $i => $item){
					if($item['slug'] != 'admin'){

						$wp_admin_bar->add_node(array(
								'id'     => 'ait-' . $item['slug'] . "-{$menuId}",
								'title'  => $item['menu-title'],
								'href'   => AitUtils::adminPageUrl(array('page' => $item['slug'])),
								'parent' => $menuId,
							));
					}
				}

				restore_current_blog();
			}
		}
	}



	public static function addPageBuilderLink($wp_admin_bar)
	{
		global $typenow;
		global $pagenow;
		global $post;

		// http://tracker.ait.sk/issues/8695
		if(!current_user_can(apply_filters('ait-admin-bar-permission', 'manage_options'))) return;

		$oid = aitOptions()->getOid();
		$args = array('page' => 'pages-options', 'oid' => $oid, 'oidnonce' => AitUtils::nonce('oidnonce'));

		if(isset($post) and $pagenow == 'post.php'){
			$args['oid'] = '_page_' . $post->ID;

			$b = aitOptions()->getFrontpage();
			if($b->customFrontpage and $b->blog == $post->ID){
				$args['oid'] = '_blog';
			}
		}

		__('Page Builder', 'ait'); // just for adminbar on frontend

		if(!is_admin() or (is_admin() and $pagenow == 'post.php' and $typenow == 'page')){
			$wp_admin_bar->add_node(array(
					'id' => 'page-builder',
					'title'  => __('Page Builder', 'ait-admin'),
					'href' => AitUtils::adminPageUrl($args),
				));
		}
	}



	public static function cssAndJs()
	{
		if(is_user_logged_in()){
			$ajaxUrl = admin_url('admin-ajax.php');

			$t = aitOptions()->getOptionsByType('theme');
			$icon = isset($t['adminBranding']['adminMenuIcon']) ? "url('{$t['adminBranding']['adminMenuIcon']}')" : 'url(' . aitPaths()->url->fw . '/admin/assets/img/ait-admin-menu-icon16.png)';


			?>
			<style>
				#wp-admin-bar-ait-admin-menu > a > .ab-icon {background-image: <?php echo $icon ?>;background-repeat: no-repeat;}
				#wpadminbar .ait-dev-mode-state{font-weight:bold;color:#fff;padding:2px 4px;border:1px solid transparent;}
				#wpadminbar .ait-dev-mode-state.state-on{color:lime;text-shadow:0 0 3px #a8ff2f;}
				#wpadminbar .ait-dev-mode.hover .ait-dev-mode-state{background:#464646;border-radius:2px;}
			</style>

			<script>
			jQuery(function($){
				$('#wp-admin-bar-ait-dev-mode > a').on('click', function(){
					var state = $(this).find('.ait-dev-mode-state');
					var v;
					if(state.hasClass('state-on')){
						state.removeClass('state-on');
						state.text('off');
						v = 0;
					}else{
						state.addClass('state-on');
						state.text('on');
						v = 1;
					}
					$.post('<?php echo $ajaxUrl ?>', {'action': 'toggleDevMode', 'value': v});
					return false;
				});

				$('#wp-admin-bar-ait-empty-theme-cache > a').on('click', function(){
					$.post('<?php echo $ajaxUrl ?>', {'action': 'emptyThemeCacheDir', '_ajax_nonce': '<?php echo AitUtils::nonce("delete-cache-theme") ?>'});
					return false;
				});

				$('#wp-admin-bar-ait-empty-wpthumb-cache > a').on('click', function(){
					$.post('<?php echo $ajaxUrl ?>', {'action': 'emptyWPThumbCacheDir', '_ajax_nonce': '<?php echo AitUtils::nonce("delete-cache-image") ?>'});
					return false;
				});
			});
			</script>
			<?php
		}
	}



	/**
	 * Toggle development mode. Also activated or deactivates dev plugins.
	 */
	public static function ajaxToggleDevMode()
	{
		$v = (int) $_POST['value'];

		$o = aitOptions()->getOptions();

		$o['theme']['administrator']['devMode'] = $v;

		update_option(aitOptions()->getOptionKey('theme'), $o['theme']);

		@unlink(aitPaths()->url->root . '/wp-content/debug.log');

		exit;
	}



	/**
	 * Empty cache dir
	 * @return void
	 */
	public static function ajaxEmptyThemeCacheDir()
	{
		AitUtils::checkAjaxNonce('delete-cache-theme');
		AitUtils::delete(aitPaths()->dir->cache, '*');
		wp_send_json_success();
	}


	/**
	 * Empty cache dir
	 * @return void
	 */
	public static function ajaxEmptyWPThumbCacheDir()
	{
		AitUtils::checkAjaxNonce('delete-cache-image');
		$u = WP_Thumb::uploadDir();
		$path = $u['basedir'] . '/cache/images';
		AitUtils::delete($path, "*");
		wp_send_json_success();
	}

}
