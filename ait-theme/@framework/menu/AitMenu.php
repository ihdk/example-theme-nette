<?php


class AitMenu
{


	public static function init()
	{
		add_action('wp_nav_menu_objects', array(__CLASS__, 'modify_nav_menu_items'), 100, 2);
		add_filter('wp_nav_menu_args', array(__CLASS__,'modify_arguments'), 100);
		add_filter('wp_edit_nav_menu_walker', array(__CLASS__,'modify_backend_walker') , 100);
		add_action('wp_update_nav_menu_item', array(__CLASS__,'update_menu'), 100, 3);
	}



	public static function modify_nav_menu_items($sorted_menu_items, $args)
	{
		foreach ($sorted_menu_items as &$menu_item) {
			if ($menu_item->title == 'menu-item-ait-column') {
				foreach ($sorted_menu_items as &$potential_parent_menu_item) {
					if ($menu_item->menu_item_parent == $potential_parent_menu_item->ID) {
						$potential_parent_menu_item->classes[] = 'menu-item-has-columns';
						continue;
					}
				}
			}
		}
		return $sorted_menu_items;
	}



	/**
	 * Replaces the default arguments for the front end menu creation with new ones
	 */
	public static function modify_arguments($arguments)
	{

		$arguments['walker'] 				= new AitMenuFrontendWalker();
		$arguments['container_class'] 		= $arguments['container_class'] .= ' megaWrapper';
		$arguments['menu_class']			= 'ait-megamenu';

		return $arguments;
	}



	/**
	 * Tells wordpress to use our backend walker instead of the default one
	 */
	public static function modify_backend_walker($name)
	{
		return 'AitMenuBackendWalker';
	}



	/**
	 * Save and Update the Custom Navigation Menu Item Properties by checking all $_POST vars with the name of $check
	 * @param int $menu_id
	 * @param int $menu_item_db
	 */
	public static function update_menu($menu_id, $menu_item_db)
	{
		$menuItemOptions = array('column-label', 'column-min-width', 'column-url', 'column-in-new-row', 'icon', 'submenu-position');

		foreach ( $menuItemOptions as $key )
		{
			if(!isset($_POST['menu-item-'.$key][$menu_item_db]))
			{
				$_POST['menu-item-'.$key][$menu_item_db] = "";
			}

			$value = $_POST['menu-item-'.$key][$menu_item_db];
			update_post_meta( $menu_item_db, '_menu-item-'.$key, $value );
		}
	}

}
