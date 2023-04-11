<?php

define('AIT_UPGRADER_PREVIOUS_THEME_VERSION', '1.49');

add_action('ait-theme-upgrade', function($upgrader){
	if(version_compare($upgrader->getThemeVersion(), '1.29', '<')){
		$upgrader->addErrors(function(){
			$errors = array();

			// do the logic here
			// update active user meta
			foreach(get_users() as $user){
				$role = reset($user->roles);
				if(strpos($role, 'cityguide_') !== FALSE){
					update_user_meta( $user->ID, 'package_name', $role );
				}
			}
			// do the logic here

			return $errors;
		});
	}
});

add_action('ait-theme-upgrade', function($upgrader){
	if(version_compare($upgrader->getThemeVersion(), '1.39', '<')){

		$upgradeThemeFn = function(){
			$errors = array();

			// do the logic here
			foreach(get_posts(array('post_type' => 'ait-item', 'posts_per_page' => -1, 'status' => 'publish')) as $post){
				// save separated meta data for featured
				$featuredPostMeta = get_post_meta( $post->ID, '_ait-item_item-featured', true );
				if(!empty($featuredPostMeta)){
					update_post_meta($post->ID, '_ait-item_item-featured', '1');
				} else {
					update_post_meta($post->ID, '_ait-item_item-featured', '0');
				}

				// if item hasn't been rated yet, create rating manually
				if (get_post_meta( $post->ID, 'rating_mean', true ) == '') {
					update_post_meta($post->ID, 'rating_mean', '0');
				}
			}
			// do the logic here

			return $errors;
		};

		$upgrader->addErrors($upgradeThemeFn());
	}
});

add_action('ait-theme-upgrade', function($upgrader){
	if(version_compare($upgrader->getThemeVersion(), '1.48', '<')){

		$upgradeThemeFn = function(){
			$errors = array();

			// do the logic here
			$args = array(
				'posts_per_page'   => -1,
				'post_type'        => 'ait-item',
				'post_status'      => 'publish',
			);

			$items = get_posts( $args );

			foreach($items as $item){
				$meta = get_post_meta($item->ID, '_ait-item_item-data', true);
				if(isset($meta) && isset($meta['subtitle'])){
					clean_post_cache( $item->ID );
					update_post_meta($item->ID, 'subtitle', $meta['subtitle']);
				}
			}
			// do the logic here

			return $errors;
		};

		$upgrader->addErrors($upgradeThemeFn());
	}
});

add_action('ait-theme-upgrade', function($upgrader){
	if(version_compare($upgrader->getParentThemeVersion(), '1.49', '<')){

		$upgradeThemeFn = function(){
			$errors = array();

			// do the logic here
			$args = array(
				'posts_per_page'   => -1,
				'post_type'        => 'ait-item',
				'post_status'      => 'publish',
			);

			$items = get_posts( $args );

			foreach($items as $item){
				$meta = get_post_meta($item->ID, '_ait-item_item-data', true);
				 if(isset($meta) && !empty($meta['features'])){
					$result = "";
					foreach ($meta['features'] as $feature) {
						$result .= $feature['text'].';'.$feature['desc'].';';
					}
					update_post_meta($item->ID, 'features_search_string', $result);
				}
			}

			/* new slugs for packages / user roles */
			// update themeoptions first
			// create new slugs for the themepackages
			global $wp_roles;
			$theme = str_replace("-child", "", sanitize_key( get_stylesheet() ) );
			$themeOptionKey = '_ait_'.$theme.'_theme_opts';	// better way to do this

			$themeOptions = get_option($themeOptionKey, array());
			foreach($themeOptions['packages']['packageTypes'] as $index => $package){
				$oldPackageSlug = "cityguide_".AitUtils::webalize($package['name']);
				$newPackageId = str_replace(".", "", uniqid("", true));
				$newPackageSlug = "cityguide_".$newPackageId;

				$themeOptions['packages']['packageTypes'][$index]['slug'] = $newPackageId;

				$oldPackageName = $package['name'];
				$oldPackageCaps = array();
				if(isset($wp_roles->roles[$oldPackageSlug])){
					$oldPackageName = $wp_roles->roles[$oldPackageSlug]['name'];
					$oldPackageCaps = $wp_roles->roles[$oldPackageSlug]['capabilities'];
					remove_role($oldPackageSlug);
				}

				add_role($newPackageSlug, $oldPackageName, $oldPackageCaps);

				// update users
				$users = get_users(array(
					'role' => $oldPackageSlug,
				));
				foreach($users as $user){
					// store old package data
					$packageActivationTime = get_user_meta($user->ID, 'package_activation_time', true);
					$packageName = get_user_meta($user->ID, 'package_name', true);

					// change old role with new role
					//$user->remove_role($oldPackageSlug);
					$user->set_role($newPackageSlug);

					// update old package data with new role
					$packageActivationTime['role'] = $newPackageSlug;
					$packageName = $newPackageSlug;

					// save updated role data
					update_user_meta($user->ID, 'package_activation_time', $packageActivationTime);
					update_user_meta($user->ID, 'package_name', $packageName);
				}

				// update item extension
				$extensionData = get_option('ait_item_extension_'.$oldPackageSlug.'_options', array());
				if(is_array($extensionData) && !empty($extensionData)){
					update_option('ait_item_extension_'.$newPackageSlug.'_options', $extensionData);
				}
			}
			update_option($themeOptionKey, $themeOptions);
			/* new slugs for packages / user roles */

			// do the logic here

			return $errors;
		};

		$upgrader->addErrors($upgradeThemeFn());
	}
});

add_action('ait-theme-upgrade', function($upgrader){
	if(version_compare($upgrader->getParentThemeVersion(), '1.50', '<')){

		$upgradeThemeFn = function(){
			$errors = array();

			// do the logic here
			/* new slugs for packages / user roles */
			// update themeoptions first
			// create new slugs for the themepackages
			global $wp_roles;
			$theme = sanitize_key( get_stylesheet() );
			$themeOptionKey = '_ait_'.$theme.'_theme_opts';	// better way to do this

			$themeOptions = get_option($themeOptionKey, array());
			if(!empty($themeOptions)){
				foreach($themeOptions['packages']['packageTypes'] as $index => $package){
					// if there is no slug present in the database => run the function again
					// child theme problem _ait_<childtheme>_theme_opts vs _ait_<parenttheme>_theme_opts problem
					if(empty($themeOptions['packages']['packageTypes'][$index]['slug'])){

						$oldPackageSlug = "cityguide_".AitUtils::webalize($package['name']);
						$newPackageId = str_replace(".", "", uniqid("", true));
						$newPackageSlug = "cityguide_".$newPackageId;

						$themeOptions['packages']['packageTypes'][$index]['slug'] = $newPackageId;

						$oldPackageName = $package['name'];
						$oldPackageCaps = array();
						if(isset($wp_roles->roles[$oldPackageSlug])){
							$oldPackageName = $wp_roles->roles[$oldPackageSlug]['name'];
							$oldPackageCaps = $wp_roles->roles[$oldPackageSlug]['capabilities'];
							remove_role($oldPackageSlug);
						}

						add_role($newPackageSlug, $oldPackageName, $oldPackageCaps);

						// update users
						$users = get_users(array(
							'role' => $oldPackageSlug,
						));
						foreach($users as $user){
							// store old package data
							$packageActivationTime = get_user_meta($user->ID, 'package_activation_time', true);
							$packageName = get_user_meta($user->ID, 'package_name', true);

							// change old role with new role
							//$user->remove_role($oldPackageSlug);
							$user->set_role($newPackageSlug);

							// update old package data with new role
							$packageActivationTime['role'] = $newPackageSlug;
							$packageName = $newPackageSlug;

							// save updated role data
							update_user_meta($user->ID, 'package_activation_time', $packageActivationTime);
							update_user_meta($user->ID, 'package_name', $packageName);
						}

						// update item extension
						$extensionData = get_option('ait_item_extension_'.$oldPackageSlug.'_options', array());
						if(is_array($extensionData) && !empty($extensionData)){
							update_option('ait_item_extension_'.$newPackageSlug.'_options', $extensionData);
						}
					}
				}
				update_option($themeOptionKey, $themeOptions);
			}
			/* new slugs for packages / user roles */

			// do the logic here

			return $errors;
		};

		$upgrader->addErrors($upgradeThemeFn());
	}
});


add_action('ait-theme-upgrade', function($upgrader){
	// var_dump(version_compare($upgrader->getParentThemeVersion(), '1.71', '<'));
	// exit;
	if(version_compare($upgrader->getParentThemeVersion(), '1.73', '<')){

		$upgradeThemeFn = function(){
			$errors = array();

			// do the logic here
			global $wpdb;

			// if this upgrade failed before there are already some items migrated
			$migratedItems = (int)get_option('ait-migrated-gps-items', 0);

			// count is set to 999999 because wp does not support -1 with offset
			$items = get_posts(array(
				"post_type" => 'ait-item',
				"posts_per_page" => 999999,
				"offset" => $migratedItems,
				"fields" => 'ids'
			));

			// prepare sql value of listed items
			$itemsList = implode( ", ", esc_sql( $items ) );

			// get all metadata of listed items
			$sql = "
				SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value
					FROM $wpdb->postmeta

					WHERE 1=1
						AND ($wpdb->postmeta.post_id IN ({$itemsList}) )
						AND ($wpdb->postmeta.meta_key = '_ait-item_item-data')"	;
			$data = $wpdb->get_results( $sql, OBJECT_K );

			$meta = array();
			foreach($data as $itemId => $row) {
				$meta = unserialize($row->meta_value);
				if($meta == false){
					continue;
				}
				// save separately meta data for lat and long and update number of migrated items
				update_post_meta($itemId, 'ait-latitude', $meta['map']['latitude']);
				update_post_meta($itemId, 'ait-longitude', $meta['map']['longitude']);
				$migratedItems++;
				update_option('ait-migrated-gps-items', $migratedItems);
			}


			// script finished successfuly and we can remove helper option
			delete_option('ait-migrated-gps-items');
			// do the logic here

			return $errors;
		};

		$upgrader->addErrors($upgradeThemeFn());
	}
});
?>