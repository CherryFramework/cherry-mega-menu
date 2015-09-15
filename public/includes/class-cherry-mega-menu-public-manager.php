<?php
/**
 * Include public script and CSS, additional functions and definitions
 *
 * @package   cherry_mega_menu
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

if ( ! class_exists( 'cherry_mega_menu_public_manager' ) ) {

	/**
	 * Menu items manager
	 */
	class cherry_mega_menu_public_manager {

		/**
		 * Class instance holder
		 * @var object
		 */
		private static $instance;

		/**
		 * include necessary files. Run actions
		 */
		public function __construct() {

			$is_enabled = cherry_mega_menu_get_option( 'mega-menu-enabled', 'true' );

			if ( 'false' === $is_enabled ) {
				return;
			}

			add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
			add_filter( 'wp_nav_menu_args', array( $this, 'add_walker_to_nav_menu' ), 999 );
			add_filter( 'wp_nav_menu_objects', array( $this, 'add_menu_objects' ), 10, 2 );
			add_filter( 'wp_nav_menu', array( $this, 'add_menu_mobile_label' ), 10, 2 );

			require_once ( 'class-cherry-mega-menu-walker.php' );
			require_once ( CHERRY_MEGA_MENU_DIR . '/core/includes/class-cherry-mega-menu-widget-manager.php' );

		}

		/**
		 * Include assets
		 *
		 * @since  1.0.0
		 */
		public function assets() {

			wp_dequeue_style( 'cherryframework4-drop-downs' );
			wp_enqueue_style(
				'font-awesome',
				'//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', array(), '4.4.0'
			);
			wp_enqueue_style(
				'cherry-mega-menu',
				CHERRY_MEGA_MENU_URI . 'public/assets/css/style.css', array(), CHERRY_MEGA_MENU_VERSION
			);

			wp_enqueue_script(
				'cherry-mega-menu',
				CHERRY_MEGA_MENU_URI . 'public/assets/js/min/script.min.js',
				array( 'jquery', 'hoverIntent' ), CHERRY_MEGA_MENU_VERSION, true
			);

			$data = array(
				'duration' => apply_filters( 'cherry-mega-menu-duration', 300 )
			);

			wp_localize_script( 'cherry-mega-menu', 'cherry_mega_menu_data', $data );
		}

		/**
		 * Apply mega menu walker for main theme menu
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $args  default nav menu args
		 * @return array         modified args with mega menu walker
		 */
		public function add_walker_to_nav_menu( $args ) {
			$mega_menu_location = cherry_mega_menu_get_option( 'mega-menu-location', array( 'primary' ) );
			$mega_menu_location = (array) $mega_menu_location;

			if ( !isset( $args['theme_location'] ) || !in_array( $args['theme_location'], $mega_menu_location ) ) {
				return $args;
			}

			$event           = cherry_mega_menu_get_option( 'mega-menu-event', 'hover' );
			$effect          = cherry_mega_menu_get_option( 'mega-menu-effect', 'slide-top' );
			$direction       = cherry_mega_menu_get_option( 'mega-menu-direction', apply_filters( 'cherry_mega_menu_default_direction', 'horizontal', $args ) );
			$parent_selector = cherry_mega_menu_get_option( 'mega-menu-parent-container', '.cherry-mega-menu' );
			$mobile_trigger  = cherry_mega_menu_get_option( 'mega-menu-mobile-trigger', 768 );

			global $cherry_mega_menu_total_columns;

			$wrapper_atts = array(
				'id'                   => '%1$s',
				'class'                => '%2$s cherry-mega-no-js cherry-mega-menu mega-menu-direction-' . $direction . ' total-columns-' . $cherry_mega_menu_total_columns,
				'data-event'           => $event,
				'data-effect'          => $effect,
				'data-direction'       => $direction,
				'data-mobile-trigger'  => $mobile_trigger,
				'data-parent-selector' => $parent_selector
			);

			/**
			 * Filter megamenu wrapper attributes
			 *
			 * @since  1.0.0
			 *
			 * @var    array  filtered attributes
			 * @param  array  $wrapper_atts default attributes
			 * @param  array  $args         default nav menu arguments
			 */
			$wrapper_atts = apply_filters( 'cherry_mega_menu_wrapper_atts', $wrapper_atts, $args );

			$atts = cherry_mega_menu_parse_atts( $wrapper_atts );

			$new_args = array(
				'container'  => 'ul',
				'menu_class' => 'menu-items',
				'items_wrap' => '<ul' . $atts . '>%3$s</ul>',
				'walker'     => new cherry_mega_menu_walker()
			);

			$args = wp_parse_args( $new_args, $args );

			return $args;
		}

		/**
		 * Add mobile menu label before menu content
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $menu  menu content
		 * @param  array   $args  menu args
		 * @return string         menu content with mobile label
		 */
		function add_menu_mobile_label( $menu, $args ) {
			// make sure we're working with a Mega Menu
			if ( ! is_a( $args->walker, 'cherry_mega_menu_walker' ) ) {
				return $menu;
			}
			$label = cherry_mega_menu_get_option( 'mega-menu-mobile-label', __( 'Menu', 'cherry-mega-menu' ) );
			$before_menu_label = '<label class="cherry-mega-menu-mobile-trigger" for="trigger-' . $args->menu_id . '">' . esc_textarea( $label ) . '</label><input class="cherry-mega-menu-mobile-trigger-box" id="trigger-' . $args->menu_id . '" type="checkbox">';
			$after_menu_label  = '<label class="cherry-mega-menu-mobile-close" for="trigger-' . $args->menu_id . '">' . __( 'Close', 'cherry-mega-menu' ) . '</label>';
			return $before_menu_label . $menu . $after_menu_label;
		}

		/**
		 * Append the widget objects to the menu array before the
		 * menu is processed by the walker.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $items  all menu item objects
		 * @param  object $args
		 * @return array          menu objects including widgets
		 */
		public function add_menu_objects( $items, $args ) {

			// make sure we're working with a Mega Menu
			if ( ! is_a( $args->walker, 'cherry_mega_menu_walker' ) ) {
				return $items;
			}

			$widget_manager = new cherry_mega_menu_widget_manager();

			foreach ( $items as $item ) {

				$saved_settings = array_filter( (array) get_post_meta( $item->ID, '_cherry_mega_menu', true ) );

				$item->megamenu_settings = wp_parse_args( $saved_settings, array(
					'type' => ''
				) );

				// only look for widgets on top level items
				if ( $item->menu_item_parent != 0 || $item->megamenu_settings['type'] != 'megamenu' ) {
					continue;
				}

				$panel_widgets = $widget_manager->get_widgets_for_menu_id( $item->ID );

				if ( empty( $panel_widgets ) ) {
					continue;
				}

				if ( ! in_array( 'menu-item-has-children', $item->classes ) ) {
					$item->classes[] = 'menu-item-has-children';
				}

				$cols = 0;

				$item_appended = $this->append_widgets( $panel_widgets, $item, $widget_manager );

				$items = array_merge( $items, $item_appended );
			}

			return $items;
		}

		/**
		 * Append saved widgets HTML to menu object
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $widgets item widgets
		 * @return array           item data
		 */
		function append_widgets( $widgets = array(), $item, $widget_manager ) {

			if ( !is_array( $widgets ) ) {
				return false;
			}

			$result = array();

			foreach ( $widgets as $widget ) {

				$cols = $widget['mega_columns'];

				$menu_item = array(
					'type'              => 'widget',
					'title'             => '',
					'content'           => $widget_manager->show_widget( $widget['widget_id'] ),
					'menu_item_parent'  => $item->ID,
					'db_id'             => 0, // This menu item does not have any childen
					'ID'                => $widget['widget_id'],
					'classes'           => array(
						"menu-item",
						"menu-item-type-widget",
						"menu-columns-" . $cols
					)
				);

				$result[] = (object) $menu_item;
			}

			return $result;

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

add_action( 'init', array( 'cherry_mega_menu_public_manager', 'get_instance' ) );