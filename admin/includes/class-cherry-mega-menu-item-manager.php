<?php
/**
 * Add cherry maega menu handlers for item manager popup etc.
 *
 * @package   cherry_mega_menu
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

if ( ! class_exists( 'cherry_mega_menu_item_manager' ) ) {

	/**
	 * Menu items manager
	 */
	class cherry_mega_menu_item_manager {

		/**
		 * Selected menu item ID
		 * @var   integer
		 * @since 1.0.0
		 */
		public $menu_item_id = 0;

		/**
		 * Selected menu item title
		 * @var   string
		 * @since 1.0.0
		 */
		public $menu_item_title = '';

		/**
		 * Selected menu item depth
		 * @var   integer
		 * @since 1.0.0
		 */
		public $menu_item_depth = 0;

		/**
		 * Selected menu item meta data
		 * @var   array
		 * @since 1.0.0
		 */
		public $menu_item_meta = array();


		/**
		 * include necessary files. Run actions
		 */
		public function __construct() {

			require_once ( 'class-cherry-mega-menu-tabs.php' );
			require_once ( CHERRY_MEGA_MENU_DIR . '/core/includes/class-cherry-mega-menu-widget-manager.php' );

			add_action( 'admin_enqueue_scripts', array( $this, 'assets' ), 40 );
			add_action( 'admin_footer', array( $this, 'popups_ui_wrap' ) );

			add_action( 'wp_ajax_cherry_mega_menu_get_popup', array( $this, 'ajax_get_popup_html' ) );
			add_action( 'wp_ajax_cherry_mega_menu_save_settings', array( $this, 'save_settings') );

		}

		/**
		 * Include assets
		 *
		 * @since  1.0.0
		 */
		public function assets( $hook ) {

			if ( 'nav-menus.php' != $hook ) {
				return;
			}

			wp_enqueue_script(
				'magnific-popup',
				CHERRY_MEGA_MENU_URI . 'admin/assets/js/min/jquery.magnific-popup.min.js', array( 'jquery' ), CHERRY_MEGA_MENU_VERSION, true
			);
			wp_enqueue_script(
				'cherry_mega_menu_iconpicker',
				CHERRY_MEGA_MENU_URI . 'admin/assets/js/min/fontawesome-iconpicker.min.js', array( 'jquery' ), CHERRY_MEGA_MENU_VERSION, true
			);
			wp_enqueue_script(
				'cherry_mega_menu_script',
				CHERRY_MEGA_MENU_URI . 'admin/assets/js/min/script.min.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-accordion', 'jquery-ui-resizable' ), CHERRY_MEGA_MENU_VERSION, true
			);

			global $cherry_mega_menu_total_columns;

			$menu_locals = array(
				'debug_launched'     => __("Launched for Menu ID", "cherry-mega-menu"),
				'launch_popup'       => __("Mega Menu", "cherry-mega-menu"),
				'saving'             => __("Saving", "cherry-mega-menu"),
				'nonce'              => wp_create_nonce('cherry_mega_menu'),
				'nonce_check_failed' => __("Oops. Something went wrong. Please reload the page.", "cherry-mega-menu"),
				'cols'               => $cherry_mega_menu_total_columns
			);

			wp_localize_script( 'cherry_mega_menu_script', 'cherry_mega_menu', $menu_locals );

			wp_enqueue_style(
				'cherry-ui-elements',
				CHERRY_MEGA_MENU_URI . 'admin/assets/css/cherry-ui.css', array(), '1.0.0'
			);
			wp_enqueue_style(
				'font-awesome',
				'//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', false, '4.4.0', 'all'
			);
			wp_enqueue_style(
				'magnific-popup',
				CHERRY_MEGA_MENU_URI . 'admin/assets/css/magnific-popup.css', array(), CHERRY_MEGA_MENU_VERSION
			);
			wp_enqueue_style(
				'cherry_mega_menu_style',
				CHERRY_MEGA_MENU_URI . 'admin/assets/css/style.css', array(), CHERRY_MEGA_MENU_VERSION
			);

		}

		/**
		 * Add popups wrapper DIV to page footer
		 *
		 * @since  1.0.0
		 */
		public function popups_ui_wrap() {

			global $pagenow;

			if ( 'nav-menus.php' != $pagenow ) {
				return;
			}

			$ui_class = cherry_mega_menu_ui_class();

			echo '<div class="' . $ui_class . ' popup-wrapper_" data-ui-class="' . $ui_class . '"></div>';
		}

		/**
		 * Set up the class vars
		 *
		 * @since 1.0
		 */
		private function init() {

			if ( isset( $_REQUEST['menu_item_id'] ) ) {

				$this->menu_item_id = absint( $_REQUEST['menu_item_id'] );

				$menu_settings = array_filter( (array) get_post_meta( $this->menu_item_id, '_cherry_mega_menu', true ) );

				$this->menu_item_meta = $menu_settings;
			}

			if ( isset( $_REQUEST['menu_item_title'] ) ) {
				$this->menu_item_title = sanitize_text_field( $_REQUEST['menu_item_title'] );
			}

			if ( isset( $_REQUEST['menu_item_depth'] ) ) {
				$this->menu_item_depth = absint( $_REQUEST['menu_item_depth'] );
			}

		}

		/**
		 * Register menu item tabs
		 *
		 * @since  1.0.0
		 *
		 * @return array menu tabs array
		 */
		public function get_item_tabs() {

			$tabs_handler = new cherry_mega_menu_item_tabs();

			$default_tabs = array(
				'mega_menu' => array(
					'title'    => __( 'Mega Menu', 'cherry-mega-menu' ),
					'callback' => array( $tabs_handler, 'mega_menu' ),
					'depth'    => 0
				),
				'settings' => array(
					'title'    => __( 'Settings', 'cherry-mega-menu' ),
					'callback' => array( $tabs_handler, 'settings' ),
					'depth'    => 0
				),
				'media' => array(
					'title'    => __( 'Media', 'cherry-mega-menu' ),
					'callback' => array( $tabs_handler, 'media' ),
					'depth'    => 10
				)
			);

			/**
			 * Filter avaliable Cherry mega menu tabs list
			 * @since  1.0.0
			 * @var    array
			 */
			$tabs = apply_filters( 'cherry_mega_menu_tabs', $default_tabs, $this->menu_item_id );

			return $tabs;
		}

		/**
		 * Get popup content
		 * @return  string  popup HTML markup
		 */
		public function get_popup() {

			$this->init();

			$tabs = $this->get_item_tabs();

			if ( !is_array($tabs) ) {
				return;
			}

			$tabs_nav     = '';
			$tabs_content = '';
			$style        = '';
			$active       = 'class="active"';

			foreach ( $tabs as $tab_id => $tab_data ) {

				if ( !isset($tab_data['title']) || !isset($tab_data['callback']) ) {
					continue;
				}

				$may_depth = isset( $tab_data['depth'] ) ? $tab_data['depth'] : 0;

				if ( $this->menu_item_depth > $may_depth ) {
					continue;
				}

				$current_tab_id = 'cherry-menu-tab-' . $tab_id;

				$tabs_nav .= '<li ' . $active . '><a href="#" data-tab="' . $current_tab_id . '">' . sanitize_text_field( $tab_data['title'] ) . '</a></li>';

				// attach callback to filter
				add_filter( 'cherry_mega_menu_tab_' . $tab_id, $tab_data['callback'], 10, 4 );
				// apply filter with callback
				$current_tab_content = apply_filters( 'cherry_mega_menu_tab_' . $tab_id, $this->menu_item_id, $this->menu_item_title, $this->menu_item_depth, $this->menu_item_meta );

				$tabs_content .= '<div id="' . $current_tab_id . '" class="mega-menu-tabs-content-item_" ' . $style . '>' . $current_tab_content . '</div>';

				$style  = 'style="visibility:hidden;"';
				$active = '';

				unset($current_tab_content);
				unset($current_tab_id);

			}

			$result  = '<form method="post" action="">';
			$result .=      '<div class="popup-heading_">';
			$result .=          '<h2 class="popup-title_">' . $this->menu_item_title . '</h2>';
			$result .=          '<a class="button-primary_ cherry-mega-menu-save-settings" href="#">' . __( 'Save', 'cherry-mega-menu' ) . '</a>';
			$result .=          '<div class="popup-saving_" style="display:none;"><span class="spinner"></span> ' . __( 'Saving...', 'cherry-mega-menu' ) . '</div>';
			$result .=          '<div class="popup-saved_" style="display:none;">' . __( 'Saved', 'cherry-mega-menu' ) . '</div>';
			$result .=      '</div>';
			$result .=      '<div class="popup-tabs_">';
			$result .=          '<ul class="vertical-tabs_ vertical-tabs_width_small_">';
			$result .=		        $tabs_nav;
			$result .=          '</ul>';
			$result .=          '<div class="mega-menu-tabs-content_">';
			$result .=		        $tabs_content;
			$result .=          '</div>';
			$result .=      '</div>';
			$result .=      '<input type="hidden" name="action" value="cherry_mega_menu_save_settings">';
			$result .=      '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'cherry_mega_menu_save_sttings' ) . '">';
			$result .=      '<input type="hidden" name="menu_item_id" value="' . $this->menu_item_id . '">';
			$result .=      '<input type="hidden" name="menu_item_depth" value="' . $this->menu_item_depth . '">';
			$result .= '</form>';

			/**
			 * Filter popup HTML than return it
			 *
			 * @since  1.0.0
			 */
			return apply_filters( 'cherry_mega_menu_popup_html', $result, $this->menu_item_id, $this->menu_item_title, $this->menu_item_depth );

		}

		/**
		 * AJAX popup handler
		 *
		 * @since  1.0.0
		 */
		public function ajax_get_popup_html() {

			$validate = check_ajax_referer( 'cherry_mega_menu', '_wpnonce', false );

			$result = array();

			if ( !$validate ) {
				$result['type']    = 'error';
				$result['content'] = __( 'Something went wrong. Please, try again', 'cherry-mega-menu' );
				wp_send_json( $result );
			}

			$result['type']    = 'success';
			$result['content'] = $this->get_popup();
			wp_send_json( $result );

		}

		/**
		 * AJAX save menu item settings
		 *
		 * @since  1.0.0
		 */
		public function save_settings() {

			check_ajax_referer( 'cherry_mega_menu_save_sttings', '_wpnonce' );

			$data    = $_REQUEST;
			$menu_id = isset( $_REQUEST['menu_item_id'] ) ? $_REQUEST['menu_item_id'] : '';

			if ( !$menu_id ) {
				die();
			}

			$data = $this->_remove_service_data( $data );

			if ( !$data || !is_array($data) ) {
				die();
			}

			$data = array_filter( $data, array( $this, '_sanitize_meta' ) );

			update_post_meta( $menu_id, '_cherry_mega_menu', $data );

			do_action( 'cherry_mega_menu_save' );

			die();
		}

		/**
		 * Remove service data array elements
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $data  request data array
		 * @return array         array without service data
		 */
		private function _remove_service_data( $data ) {

			$service_data = apply_filters( 'cherry_mega_menu_settings_service_data', array( 'action', '_wpnonce', 'menu_item_id' ) );

			foreach ( $service_data as $key ) {
				if ( isset( $data[$key] ) ) {
					unset( $data[$key] );
				}
			}

			return $data;
		}

		/**
		 * Sanitize meta inputs
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed  $var  array value
		 * @return mixed        sanitized value
		 */
		private function _sanitize_meta( $var ) {

			if ( is_array( $var ) ) {
				$var = array_filter( $data, array( $this, '_sanitize_meta' ) );
			} else {
				return sanitize_text_field( $var );
			}

		}

	}

}