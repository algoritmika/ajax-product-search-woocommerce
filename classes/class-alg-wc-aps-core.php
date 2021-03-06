<?php
/**
 * Ajax Product Search for WooCommerce  - Core Class
 *
 * @version 1.0.1
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_APS_Core' ) ) {

	class Alg_WC_APS_Core {

		/**
		 * Plugin version.
		 *
		 * @var   string
		 * @since 1.0.0
		 */
		public $version = '1.0.0';

		/**
		 * @var   Alg_WC_APS_Core The single instance of the class
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * @var Alg_WC_APS_Product_Searcher
		 */
		private $searcher;

		/**
		 * Main Alg_WC_APS_Core Instance
		 *
		 * Ensures only one instance of Alg_WC_APS_Core is loaded or can be loaded.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @static
		 * @return  Alg_WC_APS_Core - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @version 1.0.1
		 * @since   1.0.0
		 */
		function __construct() {
			// Set up localization
			$this->handle_localization();

			// Init admin part
			if(is_admin()){
				$this->init_admin();
			}

			if ( true === filter_var( get_option( Alg_WC_APS_Settings_General::OPTION_ENABLE_PLUGIN, true ), FILTER_VALIDATE_BOOLEAN ) ) {
				$this->init_frontend();
			}
		}

		/**
		 * Load scripts and styles
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		function enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Main js file
			$js_file = 'assets/dist/frontend/js/alg-wc-aps' . $suffix . '.js';
			$js_ver  = date( "ymd-Gis", filemtime( ALG_WC_APS_DIR . $js_file ) );
			wp_register_script( 'alg-wc-aps', ALG_WC_APS_URL . $js_file, array( 'jquery' ), $js_ver, true );
			wp_enqueue_script( 'alg-wc-aps' );

			$localize_obj = array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) );
			$localize_obj = apply_filters( 'alg-wc-aps-localize', $localize_obj, 'alg-wc-aps' );
			wp_localize_script( 'alg-wc-aps', 'alg_wc_aps', $localize_obj );

			// Select2
			$select2_opt = get_option( Alg_WC_APS_Settings_General::OPTION_SELECT2_ENABLE, true );
			$js_file     = 'assets/vendor/select2/js/select2' . $suffix . '.js';

			if ( filter_var( $select2_opt, FILTER_VALIDATE_BOOLEAN ) !== false ) {

				// Disable WooCommerce select 2 from some pages because it's old and conflicts with this plugin
				if ( is_checkout() || is_account_page() ) {
					wp_dequeue_script( 'select2' );
					wp_dequeue_style( 'select2' );
				}

				wp_register_script( 'alg-wc-aps-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.full.min.js', array( 'jquery' ), false, true );
				wp_enqueue_script( 'alg-wc-aps-select2' );
				wp_register_style( 'alg-wc-aps-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', array(), false );
				wp_enqueue_style( 'alg-wc-aps-select2' );
			}
		}

		/**
		 * Initialize frontend
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		protected function init_frontend(){
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 11 );

			// Initialize products searcher
			$searcher = new Alg_WC_APS_Product_Searcher();
			$this->set_searcher( $searcher );
			$searcher->init();
		}

		/**
		 * Handle Localization
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function handle_localization() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'ajax-product-search-woocommerce' );
			load_textdomain( 'ajax-product-search-woocommerce', WP_LANG_DIR . dirname( ALG_WC_APS_BASENAME ) . 'ajax-product-search-woocommerce' . '-' . $locale . '.mo' );
			load_plugin_textdomain( 'ajax-product-search-woocommerce', false, dirname( ALG_WC_APS_BASENAME ) . '/languages/' );
		}

		/**
		 * Create custom settings fields
		 *
		 * @version 1.0.1
		 * @since   1.0.1
		 */
		protected function create_custom_settings_fields() {
			WCCSO_Metabox::get_instance();
		}

		/**
		 * Init admin fields
		 *
		 * @version 1.0.1
		 * @since   1.0.0
		 */
		public function init_admin() {
			if ( !is_admin() ) {
				return;
			}

			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
			add_filter( 'plugin_action_links_' . ALG_WC_APS_BASENAME, array( $this, 'action_links' ) );

			$this->create_custom_settings_fields();

			// Admin setting options inside WooCommerce
			new Alg_WC_APS_Settings_General();
			new Alg_WC_APS_Settings_Texts();
			new Alg_WC_APS_Settings_Search();

			if ( is_admin() && get_option( 'alg_wc_aps_version', '' ) !== $this->version ) {
				update_option( 'alg_wc_aps_version', $this->version );
			}
		}

		/**
		 * Add settings tab to WooCommerce settings.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		function add_woocommerce_settings_tab( $settings ) {
			$settings[] = new Alg_WC_APS_Settings();
			return $settings;
		}

		/**
		 * Show action links on the plugin screen
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @param   mixed $links
		 * @return  array
		 */
		function action_links( $links ) {
			$custom_links = array( '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_aps' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>' );
			return array_merge( $custom_links, $links );
		}

		/**
		 * @return Alg_WC_APS_Product_Searcher
		 */
		public function get_searcher() {
			return $this->searcher;
		}

		/**
		 * @param Alg_WC_APS_Product_Searcher $searcher
		 */
		public function set_searcher( $searcher ) {
			$this->searcher = $searcher;
		}


	}
}