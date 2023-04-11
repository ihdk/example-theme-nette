<?php


/**
 * Static wrapper for Nette Cache
 */
class AitWoocommerce
{

	public static function enabled()
	{
		return aitIsPluginActive('woocommerce');
	}



	public static function init()
	{
		if(is_admin()){
			add_action('ait-create-content-custom-tables', array(__CLASS__, 'createTables'), 10, 1);
			add_filter('ait-backup-content-custom-tables', array(__CLASS__, 'addWcTablesForBackup'));
			add_filter('ait-backup-wpoptions', array(__CLASS__, 'addWcOptionsForBackup'), 10, 2);
		}

		if(!self::enabled()) return;

		// template_redirect is first place where we can use conditional tags
		add_action('template_redirect', array(__CLASS__, 'onTemplateRedirect'));

		add_filter('woocommerce_add_to_cart_fragments', array(__CLASS__, 'addToCartFragments'));
	}



	public static function addWcOptionsForBackup($options, $isDemoContent)
	{
		$options[] = 'woocommerce_shop_page_id';
		$options[] = 'woocommerce_terms_page_id';
		$options[] = 'woocommerce_cart_page_id';
		$options[] = 'woocommerce_checkout_page_id';
		$options[] = 'woocommerce_pay_page_id';
		$options[] = 'woocommerce_thanks_page_id';
		$options[] = 'woocommerce_myaccount_page_id';
		$options[] = 'woocommerce_edit_address_page_id';
		$options[] = 'woocommerce_view_order_page_id';
		$options[] = 'woocommerce_change_password_page_id';
		$options[] = 'woocommerce_logout_page_id';
		$options[] = 'woocommerce_lost_password_page_id';

		if(!$isDemoContent and self::enabled()){
			// Include settings so that we can run through defaults
			include_once(WC()->plugin_path() . '/includes/admin/class-wc-admin-settings.php');

			$settings = WC_Admin_Settings::get_settings_pages();

			foreach($settings as $section){
				foreach($section->get_settings() as $value){
					if(isset($value['default']) and isset($value['id'])){
						$options[] = $value['id'];
					}
				}

				// Special case to install the inventory settings.
				if($section instanceof WC_Settings_Products){
					foreach($section->get_settings('inventory') as $value){
						if(isset($value['default']) and isset($value['id'])){
							$options[] = $value['id'];
						}
					}
				}
			}
		}

		return array_unique($options);
	}



	public static function addWcTablesForBackup($tables)
	{
		$tables[] = "woocommerce_attribute_taxonomies";
		$tables[] = "woocommerce_termmeta";
		$tables[] = "woocommerce_downloadable_product_permissions";
		$tables[] = "woocommerce_order_items";
		$tables[] = "woocommerce_order_itemmeta";
		$tables[] = "woocommerce_tax_rates";
		$tables[] = "woocommerce_tax_rate_locations";

		return $tables;
	}



	public static function onTemplateRedirect()
	{
		if(!is_admin()){
			if(self::currentPageIs('woocommerce')){
				// we set this in template manualy
				add_filter('woocommerce_show_page_title', '__return_false');
			}
		}
	}



	public static function addToCartFragments($fragments)
	{
		$fragments['span#ait-woocomerce-cart-items-count'] = '<span id="ait-woocomerce-cart-items-count" class="cart-count">' . self::cartGetItemsCount() . '</span>';
		return $fragments;
	}



	/**
	 * WooCommerce page IDs
	 * myaccount, edit_address, change_password, shop, cart, checkout, pay, view_order, thanks, terms
	 * @param  string  $page page identifier
	 * @return int     ID of woocommerce page
	 */
	public static function getPageId($page)
	{
		if(!self::enabled()) return -1;

		$fn = function_exists('wc_get_page_id') ? 'wc_get_page_id' : 'woocommerce_get_page_id';

		return $fn($page);
	}



	/**
	 * WooCommerce page IDs
	 * myaccount, edit_address, change_password, shop, cart, checkout, pay, view_order, thanks, terms
	 * @param  string  $page page identifier
	 * @return int     ID of woocommerce page
	 */
	public static function getPage($page)
	{
		if(!self::enabled()) return NULL;

		return get_page(self::getPageId($page));
	}



	/**
	 * Checks if currently viewed page is one of these:
	 * woocommerce, shop, product, cart
	 * @param  string $page name of the page
	 * @return bool
	 */
	public static function currentPageIs($page)
	{
		if(!self::enabled()) return FALSE;

		// Returns true if on a page which uses WooCommerce templates
		// (cart and checkout are standard pages with shortcodes and thus are not included)
		if($page == 'woocommerce')
			return is_woocommerce();

		// Returns true when viewing the product type archive (shop)
		elseif($page == 'shop')
			return is_shop();

		// Returns true when viewing a single product
		elseif($page == 'product')
			return is_product();

		// Returns true when viewing the cart page
		elseif($page == 'cart')
			return is_cart();

		elseif($page == 'checkout')
			return is_checkout();

		return FALSE;
	}



	public static function cartIsEmpty()
	{
		if(!self::enabled()) return true;

		global $woocommerce;

		return ($woocommerce->cart->get_cart_contents_count() != 0);
	}



	public static function cartGetItemsCount()
	{
		if(!self::enabled()) return 0;

		global $woocommerce;

		return $woocommerce->cart->get_cart_contents_count();
	}



	public static function cartSubtotal()
	{
		if(!self::enabled()) return 0;

		global $woocommerce;

		return $woocommerce->cart->get_cart_subtotal();
	}



	public static function cartDisplay()
	{
		if(!self::enabled()) return '';

		ob_start();
		the_widget('WC_Widget_Cart', array(
				'ait-dropdown-wc-cart-widget' => true,
			)
			, array(
				'before_title' => '',
				'after_title' => ''
		));

		$out = ob_get_clean();

		return $out;
	}


	public static function productCategoriesDisplay()
	{
		if(!self::enabled()) return '';

		ob_start();
		the_widget('WC_Widget_Product_Categories',
		array(
			'title' => NULL,
			'orderby' => 'name'
		),array(
				'before_title' => '',
				'after_title' => ''
		));

		$out = ob_get_clean();

		return $out;
	}



	public static function cartUrl()
	{
		if(!self::enabled()) return '#';

		global $woocommerce;

		return $woocommerce->cart->get_cart_url();
	}



	public static function isRegistrationEnabled()
	{
		return get_option('woocommerce_enable_myaccount_registration') == 'yes';
	}



	public static function accountUrl()
	{
		if(!self::enabled()) return '#';

		$fn = function_exists('wc_get_page_id') ? 'wc_get_page_id' : 'woocommerce_get_page_id';

		return get_permalink($fn('myaccount'));
	}



	/**
	 * Creates tables
	 * Copy from woocommerce\includes\class-wc-install.php
	 * @return void
	 */
	public static function createTables($isDemoContent)
	{
		if(!$isDemoContent) return;

		global $wpdb;

		// test sample table if exists, if so then do nothing, WooCommerce is probably installed
		 if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_attribute_taxonomies'")){
			return;
		}

		$wpdb->hide_errors();

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty($wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/**
		 * Update schemas before DBDELTA
		 *
		 * Before updating, remove any primary keys which could be modified due to schema updates
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_downloadable_product_permissions';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_downloadable_product_permissions` LIKE 'permission_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions DROP PRIMARY KEY, ADD `permission_id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
			}
		}

		// WooCommerce Tables
		$woocommerce_tables = "
	CREATE TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies (
	  attribute_id bigint(20) NOT NULL auto_increment,
	  attribute_name varchar(200) NOT NULL,
	  attribute_label longtext NULL,
	  attribute_type varchar(200) NOT NULL,
	  attribute_orderby varchar(200) NOT NULL,
	  attribute_public int(1) NOT NULL DEFAULT 1,
	  PRIMARY KEY  (attribute_id),
	  KEY attribute_name (attribute_name)
	) $collate;
	CREATE TABLE {$wpdb->prefix}woocommerce_termmeta (
	  meta_id bigint(20) NOT NULL auto_increment,
	  woocommerce_term_id bigint(20) NOT NULL,
	  meta_key varchar(255) NULL,
	  meta_value longtext NULL,
	  PRIMARY KEY  (meta_id),
	  KEY woocommerce_term_id (woocommerce_term_id),
	  KEY meta_key (meta_key)
	) $collate;
	CREATE TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions (
	  permission_id bigint(20) NOT NULL auto_increment,
	  download_id varchar(32) NOT NULL,
	  product_id bigint(20) NOT NULL,
	  order_id bigint(20) NOT NULL DEFAULT 0,
	  order_key varchar(200) NOT NULL,
	  user_email varchar(200) NOT NULL,
	  user_id bigint(20) NULL,
	  downloads_remaining varchar(9) NULL,
	  access_granted datetime NOT NULL default '0000-00-00 00:00:00',
	  access_expires datetime NULL default null,
	  download_count bigint(20) NOT NULL DEFAULT 0,
	  PRIMARY KEY  (permission_id),
	  KEY download_order_key_product (product_id,order_id,order_key,download_id),
	  KEY download_order_product (download_id,order_id,product_id)
	) $collate;
	CREATE TABLE {$wpdb->prefix}woocommerce_order_items (
	  order_item_id bigint(20) NOT NULL auto_increment,
	  order_item_name longtext NOT NULL,
	  order_item_type varchar(200) NOT NULL DEFAULT '',
	  order_id bigint(20) NOT NULL,
	  PRIMARY KEY  (order_item_id),
	  KEY order_id (order_id)
	) $collate;
	CREATE TABLE {$wpdb->prefix}woocommerce_order_itemmeta (
	  meta_id bigint(20) NOT NULL auto_increment,
	  order_item_id bigint(20) NOT NULL,
	  meta_key varchar(255) NULL,
	  meta_value longtext NULL,
	  PRIMARY KEY  (meta_id),
	  KEY order_item_id (order_item_id),
	  KEY meta_key (meta_key)
	) $collate;
	CREATE TABLE {$wpdb->prefix}woocommerce_tax_rates (
	  tax_rate_id bigint(20) NOT NULL auto_increment,
	  tax_rate_country varchar(200) NOT NULL DEFAULT '',
	  tax_rate_state varchar(200) NOT NULL DEFAULT '',
	  tax_rate varchar(200) NOT NULL DEFAULT '',
	  tax_rate_name varchar(200) NOT NULL DEFAULT '',
	  tax_rate_priority bigint(20) NOT NULL,
	  tax_rate_compound int(1) NOT NULL DEFAULT 0,
	  tax_rate_shipping int(1) NOT NULL DEFAULT 1,
	  tax_rate_order bigint(20) NOT NULL,
	  tax_rate_class varchar(200) NOT NULL DEFAULT '',
	  PRIMARY KEY  (tax_rate_id),
	  KEY tax_rate_country (tax_rate_country),
	  KEY tax_rate_state (tax_rate_state),
	  KEY tax_rate_class (tax_rate_class),
	  KEY tax_rate_priority (tax_rate_priority)
	) $collate;
	CREATE TABLE {$wpdb->prefix}woocommerce_tax_rate_locations (
	  location_id bigint(20) NOT NULL auto_increment,
	  location_code varchar(255) NOT NULL,
	  tax_rate_id bigint(20) NOT NULL,
	  location_type varchar(40) NOT NULL,
	  PRIMARY KEY  (location_id),
	  KEY tax_rate_id (tax_rate_id),
	  KEY location_type (location_type),
	  KEY location_type_code (location_type,location_code)
	) $collate;
	";
		dbDelta( $woocommerce_tables );
	}
}
