<?php
/**
 * Plugin Name: Cherry Mega Menu
 * Plugin URI:  http://www.cherryframework.com/
 * Description: A megamenu management plugin for WordPress.
 * Version:     1.0.4
 * Author:      Cherry Team
 * Author URI:  http://www.cherryframework.com/
 * Text Domain: cherry-mega-menu
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

if ( ! class_exists( 'cherry_mega_menu' ) ) {
	/**
	 * Main plugin class
	 */
	final class cherry_mega_menu {

		/**
		 * @var   string
		 * @since 1.0.0
		 */
		public $version = '1.0.4';

		/**
		 * @var   string
		 * @since 1.0.0
		 */
		public $slug = 'cherry-mega-menu';

		/**
		 * Constructor
		 */
		public function __construct() {

			// Register activation and deactivation hooks.
			register_activation_hook( __FILE__, array( $this, 'activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

			// Internationalize the text strings used.
			add_action( 'plugins_loaded', array( $this, 'lang' ), 2 );

			add_action( 'cherry_mega_menu_after_widget_add', array( $this, 'clear_caches' ) );
			add_action( 'cherry_mega_menu_after_widget_save', array( $this, 'clear_caches' ) );
			add_action( 'cherry_mega_menu_after_widget_delete', array( $this, 'clear_caches' ) );

			add_filter( 'cherry_data_manager_menu_meta', array( $this, 'menu_meta_keys' ) );

			add_filter( 'cherry_compiler_static_css', array( $this, 'add_style_to_compiler' ) );

			// Set the constants needed by the plugin.
			$this->constants();

			$GLOBALS['cherry_mega_menu_total_columns'] = apply_filters( 'cherry_mega_menu_total_columns', 12 );

			$this->includes();

			// init menu caching class
			add_action( 'init', array( 'cherry_mega_menu_cache', 'get_instance' ) );

			if ( is_admin() ) {
				$this->_admin();
			} else {
				$this->_public();
			}
		}

		/**
		 * Pass chart style handle to CSS compiler
		 *
		 * @since  1.0.0
		 *
		 * @param  array $handles CSS handles to compile
		 */
		function add_style_to_compiler( $handles ) {
			$handles = array_merge(
				array( 'cherry-mega-menu' => CHERRY_MEGA_MENU_URI . 'public/assets/css/style.css' ),
				$handles
			);

			return $handles;
		}

		/**
		 * Initialise translations
		 *
		 * @since 1.0.0
		 */
		public function lang() {
			load_plugin_textdomain( $this->slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Do this after plugin activation
		 *
		 * @since 1.0.0
		 */
		function activation() {
			/**
			 * Fires default cherry activation action
			 */
			do_action( 'cherry_plugin_activate' );
		}

		/**
		 * Do this after plugin deactivation
		 *
		 * @since 1.0.0
		 */
		function deactivation() {
			/**
			 * Fires default cherry deactivation action
			 */
			do_action( 'cherry_plugin_deactivate' );
		}

		/**
		 * Defines constants for the plugin.
		 *
		 * @since 1.0.0
		 */
		function constants() {

			/**
			 * Set the version number of the plugin.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_MEGA_MENU_VERSION', $this->version );

			/**
			 * Set the slug of the plugin.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_MEGA_MENU_SLUG', basename( dirname( __FILE__ ) ) );

			/**
			 * Set constant path to the plugin directory.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_MEGA_MENU_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

			/**
			 * Set constant path to the plugin URI.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_MEGA_MENU_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

		}

		/**
		 * Include core files for both: admin and public
		 *
		 * @since 1.0.0
		 */
		function includes() {
			require_once( 'core/includes/cherry-mega-menu-core-functions.php' );
			require_once( 'core/includes/class-cherry-mega-menu-cache.php' );
		}

		/**
		 * Include files and assign actions and filters for admin
		 *
		 * @since 1.0.0
		 */
		private function _admin() {

			require_once( 'admin/includes/class-cherry-mega-menu-options.php' );
			require_once( 'admin/includes/class-cherry-mega-menu-item-manager.php' );
			new cherry_mega_menu_item_manager();
			new cherry_mega_menu_widget_manager();

			require_once( CHERRY_MEGA_MENU_DIR . 'admin/includes/class-cherry-update/class-cherry-plugin-update.php' );

			$Cherry_Plugin_Update = new Cherry_Plugin_Update();
			$Cherry_Plugin_Update -> init( array(
					'version'			=> CHERRY_MEGA_MENU_VERSION,
					'slug'				=> CHERRY_MEGA_MENU_SLUG,
					'repository_name'	=> CHERRY_MEGA_MENU_SLUG
			));
		}

		/**
		 * Include files and assign actions and filters for public
		 *
		 * @since 1.0.0
		 */
		private function _public() {
			require_once( 'public/includes/class-cherry-mega-menu-public-manager.php' );
		}

		/**
		 * Pass mega menu item meta key to content importer
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $keys initial keys array
		 * @return array
		 */
		function menu_meta_keys( $keys ) {
			$keys[] = '_cherry_mega_menu';
			return $keys;
		}

		/**
		 * Checks this WordPress installation is v3.8 or above.
		 * 3.8 is needed for dashicons.
		 *
		 * @since 1.0.0
		 */
		public function is_compatible_wordpress_version() {
			global $wp_version;

			return $wp_version >= 3.8;
		}

		/**
		 * Clear the cache when the Cherry Mega Menu is updated.
		 *
		 * @since 1.0.0
		 */
		public function clear_caches() {
			// https://wordpress.org/plugins/widget-output-cache/
			if ( function_exists( 'menu_output_cache_bump' ) ) {
				menu_output_cache_bump();
			}

			// https://wordpress.org/plugins/widget-output-cache/
			if ( function_exists( 'widget_output_cache_bump' ) ) {
				widget_output_cache_bump();
			}

			// https://wordpress.org/plugins/wp-super-cache/
			if ( function_exists( 'wp_cache_clear_cache' ) ) {
				global $wpdb;
				wp_cache_clear_cache( $wpdb->blogid );
			}
		}

	}

	new cherry_mega_menu();
}