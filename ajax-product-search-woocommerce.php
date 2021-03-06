<?php
/*
Plugin Name: Ajax Product Search for WooCommerce by Algoritmika
Description: Provides an autocomplete feature to search WooCommerce products
Version: 1.0.9
Author: Algoritmika Ltd
Author URI: http://algoritmika.com
Copyright: © 2018 Algoritmika Ltd.
WC requires at least: 3.0.0
WC tested up to: 3.5
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: ajax-product-search-woocommerce
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Disable this plugin if Pro version is activated
if ( defined( 'ALG_WC_APS_PRO_DIR' ) ) {
	add_action( 'admin_init', function() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	} );
}

// Check if WooCommerce is active
$plugin = 'woocommerce/woocommerce.php';
if (
	! in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) &&
	! ( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
) {
	return;
}

// Autoloader without namespace
if ( ! function_exists( 'alg_wc_aps_autoloader' ) ) {

	/**
	 * Autoloads all classes
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @param   type $class
	 */
	function alg_wc_aps_autoloader( $class ) {
		if ( false !== strpos( $class, 'Alg_WC_APS' ) ) {
			$classes_dir     = array();
			$plugin_dir_path = realpath( plugin_dir_path( __FILE__ ) );
			$classes_dir[0]  = $plugin_dir_path . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
			$classes_dir[1]  = $plugin_dir_path . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;
			$classes_dir[2]  = $plugin_dir_path . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR;
			$class_file      = 'class-' . strtolower( str_replace( array( '_', "\0" ), array( '-', '' ), $class ) . '.php' );

			foreach ( $classes_dir as $key => $dir ) {
				$file = $dir . $class_file;
				if ( is_file( $file ) ) {
					require_once $file;
					break;
				}
			}
		}
	}

	spl_autoload_register( 'alg_wc_aps_autoloader' );
}

// Constants
if ( ! defined( 'ALG_WC_APS_DIR' ) ) {
	define( 'ALG_WC_APS_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'ALG_WC_APS_URL' ) ) {
	define( 'ALG_WC_APS_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ALG_WC_APS_BASENAME' ) ) {
	define( 'ALG_WC_APS_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'ALG_WC_APS_FOLDER_NAME' ) ) {
	define( 'ALG_WC_APS_FOLDER_NAME', untrailingslashit( plugin_dir_path( plugin_basename( __FILE__ ) ) ) );
}

if ( ! function_exists( 'alg_ajax_product_search_for_wc' ) ) {
	/**
	 * Returns the main instance of Alg_WC_APS_Core to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  Alg_WC_APS_Core
	 */
	function alg_ajax_product_search_for_wc() {
		return Alg_WC_APS_Core::instance();
	}
}

// Start plugin
add_action( 'plugins_loaded', 'alg_wc_aps_start_plugin' );
if ( ! function_exists( 'alg_wc_aps_start_plugin' ) ) {
	/**
	 * Starts the plugin
	 *
	 * @version 1.0.1
	 * @since   1.0.1
	 */
	function alg_wc_aps_start_plugin() {
		// Includes composer dependencies
		require __DIR__ . '/vendor/autoload.php';
		
		alg_ajax_product_search_for_wc();
	}
}
