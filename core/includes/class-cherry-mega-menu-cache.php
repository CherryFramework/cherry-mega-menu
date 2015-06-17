<?php
/**
 * Menu caching manager
 *
 * @package   cherry_mega_menu
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

if ( ! class_exists( 'cherry_mega_menu_cache' ) ) {

	class cherry_mega_menu_cache {

		/**
		 * Class instance holder
		 * @var object
		 */
		private static $instance;

		/**
		 * Service vars
		 */
		public $nav_prefix  = 'wp_nav_menu-';
		public $item_prefix = 'wp_nav_items-';
		public $transient   = 'wp_nav_menus';

		function __construct() {

			$menu_enabled = cherry_mega_menu_get_option( 'mega-menu-enabled', 'true' );

			if ( 'false' === $menu_enabled ) {
				return;
			}

			$cache_enabled = cherry_mega_menu_get_option( 'mega-menu-cache' );

			if ( 'true' != $cache_enabled ) {
				return;
			}

			if ( is_admin() ) {
				$this->_admin();
			} else {
				$this->_public();
			}
		}

		/**
		 * init admin part
		 *
		 * @since 1.0.0
		 */
		function _admin() {
			// reset caches on specific actions
			add_action( 'wp_create_nav_menu', array( &$this, 'clear_cache' ) );
			add_action( 'wp_update_nav_menu', array( &$this, 'clear_cache' ) );
			add_action( 'wp_delete_nav_menu', array( &$this, 'clear_cache' ) );
			add_action( 'cherry-options-updated', array( &$this, 'clear_cache' ) );
			add_action( 'cherry-section-restored', array( &$this, 'clear_cache' ) );
			add_action( 'cherry-options-restored', array( &$this, 'clear_cache' ) );
			add_action( 'save_post', array( &$this, 'clear_cache' ) );
			add_action( 'cherry_mega_menu_save', array( &$this, 'clear_cache' ) );
		}

		/**
		 * init public part
		 *
		 * @since 1.0.0
		 */
		function _public() {
			add_filter( 'pre_wp_nav_menu', array( &$this, 'return_cached_menu' ), 0, 2 );
			add_filter( 'wp_nav_menu', array( &$this, 'set_cached_menu' ), 999, 2 );
		}

		/**
		 * Get menu cache for current page
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $menu  menu content
		 * @param  array   $args  menu args
		 * @return string         menu content with mobile label
		 */
		function return_cached_menu( $menu, $args ) {

			// make sure we're working with a Mega Menu
			if ( ! is_a( $args->walker, 'cherry_mega_menu_walker' ) ) {
				return null;
			}

			global $wp_query;

			$key = false;

			if ( is_front_page() ) {
				$key = 'front_page';
			} elseif ( is_home() ) {
				$key = 'home_page';
			} elseif ( is_singular() && property_exists( $wp_query, 'queried_object_id' ) ) {
				$cur_object_id = (int) $wp_query->queried_object_id;
				$key = 'single-' . $cur_object_id;
			} elseif ( property_exists( $wp_query, 'queried_object' ) && null != $wp_query->queried_object ) {
				$cur_object_id = (int) $wp_query->queried_object_id;
				$key = 'term-' . $cur_object_id;
			} else {
				$key = 'default';
			}

			$current_cache = get_transient( $this->transient );

			if ( ! $key || empty( $current_cache[$key] ) ) {
				return null;
			}

			return $current_cache[$key];
		}

		/**
		 * Save menu output for current page to transient
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $menu  menu content
		 * @param  array   $args  menu args
		 * @return string         menu content with mobile label
		 */
		function set_cached_menu( $menu, $args ) {

			// make sure we're working with a Mega Menu
			if ( ! is_a( $args->walker, 'cherry_mega_menu_walker' ) ) {
				return $menu;
			}

			$current_cache = get_transient( $this->transient );

			$key = $this->get_page_key();

			if ( false !== $key ) {
				$current_cache[$key] = $menu;
			}

			set_transient( $this->transient, $current_cache, 3*DAY_IN_SECONDS );

			return $menu;
		}

		/**
		 * Get page key to search it in transient
		 *
		 * @since 1.0.0
		 */
		public function get_page_key() {

			global $wp_query;

			$has_active = wp_cache_get( 'cherry_menu_has_active', 'cherry' );

			$key = false;

			if ( 'has' == $has_active ) {
				// check, what key pass to cache
				if ( is_front_page() ) {
					$key = 'front_page';
				} elseif ( is_home() ) {
					$key = 'home_page';
				} elseif ( is_singular() ) {
					$cur_object_id = (int) $wp_query->queried_object_id;
					$key = 'single-' . $cur_object_id;
				} elseif ( null != $wp_query->queried_object ) {
					$cur_object_id = (int) $wp_query->queried_object_id;
					$key = 'term-' . $cur_object_id;
				}
			} else {
				$key = 'default';
			}

			return $key;
		}

		/**
		 * Delete cache
		 *
		 * @since 1.0.0
		 */
		public function clear_cache() {
			delete_transient( $this->transient );
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance )
				self::$instance = new self;

			return self::$instance;
		}


	}

}