<?php
/**
 * Widgets manager
 *
 * based on Widgets maanger from Mega Menu plugin - http://www.maxmegamenu.com/
 *
 * @author 		Cherry Team
 * @category 	Core
 * @package 	cherry-woocommerce-package/class
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

if ( ! class_exists('cherry_mega_menu_widget_manager') ) :

/**
 * Processes AJAX requests from the Mega Menu panel editor.
 * Also registers our widget sidebar.
 *
 * There is very little in WordPress core to help with listing, editing, saving,
 * deleting widgets etc so this class implements that functionality.
 */
class cherry_mega_menu_widget_manager {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'register_sidebar' ) );

		add_action( 'wp_ajax_cherry_mega_menu_edit_widget',    array( $this, 'show_widget_form' ) );
		add_action( 'wp_ajax_cherry_mega_menu_save_widget',    array( $this, 'save_widget' ) );
		add_action( 'wp_ajax_cherry_mega_menu_update_columns', array( $this, 'update_columns' ) );
		add_action( 'wp_ajax_cherry_mega_menu_delete_widget',  array( $this, 'delete_widget' ) );
		add_action( 'wp_ajax_cherry_mega_menu_add_widget',     array( $this, 'add_widget' ) );
		add_action( 'wp_ajax_cherry_mega_menu_move_widget',    array( $this, 'move_widget' ) );

		add_filter( 'widget_update_callback', array( $this, 'persist_cherry_mega_menu_widget_settings'), 10, 4 );

	}


	/**
	 * Depending on how a widget has been written, it may not necessarily base the new widget settings on
	 * a copy the old settings. If this is the case, the mega menu data will be lost. This function
	 * checks to make sure widgets persist the mega menu data when they're saved.
	 *
	 * @since 1.0.0
	 */
	public function persist_cherry_mega_menu_widget_settings( $instance, $new_instance, $old_instance, $that ) {

		if ( isset( $old_instance["mega_menu_columns"] ) && ! isset( $new_instance["mega_menu_columns"] ) ) {
			$instance["mega_menu_columns"] = $old_instance["mega_menu_columns"];
		}

		if ( isset( $old_instance["mega_menu_parent_menu_id"] ) && ! isset( $new_instance["mega_menu_parent_menu_id"] ) ) {
			$instance["mega_menu_parent_menu_id"] = $old_instance["mega_menu_parent_menu_id"];
		}

		return $instance;
	}


	/**
	 * Create our own widget area to store all mega menu widgets.
	 * All widgets from all menus are stored here, they are filtered later
	 * to ensure the correct widgets show under the correct menu item.
	 *
	 * @since 1.0.0
	 */
	public function register_sidebar() {

		register_sidebar(
			array(
				'id'          => 'cherry-mega-menu',
				'name'        => __("Cherry Mega Menu Widgets", "cherry-mega-menu"),
				'description' => __("Do not manually edit this area.", "cherry-mega-menu")
			)
		);
	}


	/**
	 * Display a widget settings form
	 *
	 * @since 1.0.0
	 */
	public function show_widget_form() {

		check_ajax_referer( 'cherry_mega_menu', '_wpnonce' );

		$widget_id = sanitize_text_field( $_POST['widget_id'] );

		wp_die( $this->_show_widget_form( $widget_id ) );

	}


	/**
	 * Save a widget
	 *
	 * @since 1.0.0
	 */
	public function save_widget() {

		$widget_id = sanitize_text_field( $_POST['widget_id'] );
		$id_base = sanitize_text_field( $_POST['id_base'] );

		check_ajax_referer( 'megamenu_save_widget_' . $widget_id );

		$saved = $this->_save_widget( $id_base );

		$message = ($saved) ?
			sprintf( __("Saved %s", "cherry-mega-menu"), $id_base ) :
			sprintf( __("Failed to save %s", "cherry-mega-menu"), $id_base );

		do_action( "cherry_mega_menu_after_widget_save" );
		do_action( "cherry_mega_menu_save" );

		wp_die( $message );

	}


	/**
	 * Update the number of mega columns for a widget
	 *
	 * @since 1.0.0
	 */
	public function update_columns() {

		check_ajax_referer( 'cherry_mega_menu', '_wpnonce' );

		$widget_id = isset( $_REQUEST['widget_id'] ) ? sanitize_text_field( $_REQUEST['widget_id'] ) : '';
		$columns = isset( $_REQUEST['columns'] ) ? absint( $_REQUEST['columns'] ) : 1;

		$updated = $this->_update_columns( $widget_id, $columns );

		$message = ( $updated ) ?
			sprintf( __( "Updated %s (new columns: %d)", "cherry-mega-menu"), $widget_id, $columns ) :
			sprintf( __( "Failed to update %s", "cherry-mega-menu"), $widget_id );

		do_action( "cherry_mega_menu_after_widget_save" );
		do_action( "cherry_mega_menu_save" );

		wp_die( $message );

	}


	/**
	 * Add a widget to the panel
	 *
	 * @since 1.0.0
	 */
	public function add_widget() {

		check_ajax_referer( 'cherry_mega_menu', '_wpnonce' );

		$id_base      = isset( $_REQUEST['id_base'] ) ? sanitize_text_field( $_REQUEST['id_base'] ) : '';
		$menu_item_id = isset( $_REQUEST['menu_item_id'] ) ? absint( $_REQUEST['menu_item_id'] ) : '';
		$title        = isset( $_REQUEST['title'] ) ? sanitize_text_field( $_REQUEST['title'] ) : '';
		$total_cols   = isset( $_REQUEST['total_cols'] ) ? absint( $_REQUEST['total_cols'] ) : '';

		$added = $this->_add_widget( $id_base, $menu_item_id, $title, $total_cols );

		$result = array(
			'type'  => 'success'
		);

		if ( $added && is_array( $added ) ) {
			$result = array_merge( $result, $added );
		} else {
			$result['type']    = 'error';
			$result['content'] = sprintf( __("Failed to add %s to %d", "cherry-mega-menu"), $id_base, $menu_item_id );
		}

		do_action( "cherry_mega_menu_after_widget_add" );
		do_action( "cherry_mega_menu_save" );

		wp_send_json( $result );

	}


	/**
	 * Deletes a widget
	 *
	 * @since 1.0.0
	 */
	public function delete_widget() {

		check_ajax_referer( 'cherry_mega_menu', '_wpnonce' );

		$widget_id = sanitize_text_field( $_POST['widget_id'] );

		$deleted = $this->_delete_widget( $widget_id );

		$message = ( $deleted ) ?
		  sprintf( __( "Deleted %s", "cherry-mega-menu"), $widget_id ) :
		  sprintf( __( "Failed to delete %s", "cherry-mega-menu"), $widget_id );

		do_action( "megamenu_after_widget_delete" );
		do_action( "cherry_mega_menu_save" );

		wp_die( $message );

	}


	/**
	 * Moves a widget to a new position
	 *
	 * @since 1.0.0
	 */
	public function move_widget() {

		check_ajax_referer( 'cherry_mega_menu', '_wpnonce' );

		$widget_to_move = sanitize_text_field( $_POST['widget_id'] );
		$position = absint( $_POST['position'] );
		$menu_item_id = absint( $_POST['menu_item_id'] );

		$moved = $this->_move_widget( $widget_to_move, $position, $menu_item_id );

		$message = ( $moved ) ?
			sprintf( __( "Moved %s to %d (%s)", "cherry-mega-menu"), $widget_to_move, $position, json_encode($moved) ) :
			sprintf( __( "Failed to move %s to %d", "cherry-mega-menu"), $widget_to_move, $position );

		do_action( "cherry_mega_menu_after_widget_save" );
		do_action( "cherry_mega_menu_save" );

		wp_die( $message );

	}


	/**
	 * Returns an object representing all widgets registered in WordPress
	 *
	 * @since 1.0.0
	 */
	public function get_available_widgets() {
		global $wp_widget_factory;

		$widgets = array();

		foreach( $wp_widget_factory->widgets as $widget ) {

			$disabled_widgets = array('megamenu');

			$disabled_widgets = apply_filters("megamenu_incompatible_widgets", $disabled_widgets );

			if ( ! in_array( $widget->id_base, $disabled_widgets ) ) {

				$widgets[] = array(
					'text'        => $widget->name,
					'value'       => $widget->id_base,
					'description' => isset( $widget->widget_options['description'] ) ? $widget->widget_options['description'] : ''
				);

			}

		}

		uasort( $widgets, array( $this, 'sort_by_text' ) );

		return $widgets;

	}


	/**
	 * Sorts a 2d array by the 'text' key
	 *
	 * @since 1.2
	 * @param array $a
	 * @param array $b
	 */
	function sort_by_text( $a, $b ) {
		return strcmp( $a['text'], $b['text'] );
	}


	/**
	 * Returns an array of all widgets belonging to a specified menu item ID.
	 *
	 * @since 1.0.0
	 * @param int $menu_item_id
	 */
	public function get_widgets_for_menu_id( $menu_item_id ) {

		$widgets = array();

		if ( $mega_menu_widgets = $this->get_sidebar_widgets() ) {

			foreach ( $mega_menu_widgets as $widget_id ) {

				$settings = $this->get_settings_for_widget_id( $widget_id );

				if ( isset( $settings['mega_menu_parent_menu_id'] ) && $settings['mega_menu_parent_menu_id'] == $menu_item_id ) {

					$widget_data = $this->parse_widget_id( $widget_id );
					$name = $widget_data['name'];

					$widgets[ $widget_id ] = array(
						'widget_id'    => $widget_id,
						'title'        => $name,
						'mega_columns' => $settings['mega_menu_columns']
					);

				}

			}

		}

		return $widgets;

	}


	/**
	 * Returns the saved settings for a specific widget.
	 *
	 * @since 1.0.0
	 * @param $widget_id - id_base-ID (eg meta-3)
	 */
	public function get_settings_for_widget_id( $widget_id ) {
		global $wp_registered_widgets;

		if (!isset($wp_registered_widgets[ $widget_id ])) {
			return false;
		}

		$registered_widget = $wp_registered_widgets[ $widget_id ];

		// instantiate the widget so we can get access to the settings
		$class_name = get_class( $registered_widget['callback'][0] );

		$widget_object = new $class_name;

		$all_settings    = $widget_object->get_settings();
		$widget_data     = $this->parse_widget_id( $widget_id );
		$widget_number   = $widget_data['id'];
		$widget_settings = $all_settings[$widget_number];

		return $widget_settings;


	}


	/**
	 * Returns the id(number), name/title and base id of a Widget
	 *
	 * @since 1.0.0
	 * @param $widget_id - id_base-ID (eg meta-3)
	 */
	public function parse_widget_id( $widget_id ) {
		global $wp_registered_widgets, $wp_registered_widget_controls;

		$result = array(
			'id'      => '',
			'name'    => '',
			'id_base' => ''
		);

		$parts             = explode( "-", $widget_id );
		$registered_widget = $wp_registered_widgets[$widget_id];
		$control           = $wp_registered_widget_controls[ $widget_id ];

		$result['id']      = absint( end( $parts ) );
		$result['name']    = $registered_widget['name'];
		$result['id_base'] = isset( $control['id_base'] ) ? $control['id_base'] : $control['id'];

		return $result;
	}

	/**
	 * Returns the HTML for a single widget instance.
	 *
	 * @since 1.0.0
	 * @param string widget_id Something like meta-3
	 */
	public function show_widget( $id ) {
		global $wp_registered_widgets;

		$params = array_merge(
			array( array_merge( array( 'widget_id' => $id, 'widget_name' => $wp_registered_widgets[$id]['name'] ) ) ),
			(array) $wp_registered_widgets[$id]['params']
		);

		$params[0]['before_title'] = '<h4 class="mega-block-title">';
		$params[0]['after_title'] = '</h4>';
		$params[0]['before_widget'] = "";
		$params[0]['after_widget'] = "";

		$callback = $wp_registered_widgets[$id]['callback'];

		if ( is_callable( $callback ) ) {
			ob_start();
			call_user_func_array( $callback, $params );
			return ob_get_clean();
		}

	}

	/**
	 * Shows the widget edit form for the specified widget.
	 *
	 * @since 1.0.0
	 * @param $widget_id - id_base-ID (eg meta-3)
	 */
	public function _show_widget_form( $widget_id ) {
		global $wp_registered_widget_controls;

		$control       = $wp_registered_widget_controls[ $widget_id ];
		$widget_data   = $this->parse_widget_id( $widget_id );
		$id_base       = $widget_data['id_base'];
		$widget_number = $widget_data['id'];

		$nonce = wp_create_nonce('megamenu_save_widget_' . $widget_id);

		?>

		<form method='post'>
			<input type='hidden' name='action'    value='cherry_mega_menu_save_widget' />
			<input type='hidden' name='id_base'   value='<?php echo $id_base; ?>' />
			<input type='hidden' name='widget_id' value='<?php echo $widget_id ?>' />
			<input type='hidden' name='_wpnonce'  value='<?php echo $nonce ?>' />

			<?php
				if ( is_callable( $control['callback'] ) ) {
					call_user_func_array( $control['callback'], $control['params'] );
				}
			?>

			<div class='widget-controls'>
				<a class='delete' href='#delete'>Delete</a> |
				<a class='close' href='#close'>Close</a>
			</div>

			<?php
				submit_button( __( 'Save' ), 'button-primary alignright', 'savewidget', false );
			?>

			<span class='spinner' style='display: none;'></span>
		</form>

		<?php
	}


	/**
	 * Saves a widget. Calls the update callback on the widget.
	 * The callback inspects the post values and updates all widget instances which match the base ID.
	 *
	 * @since 1.0.0
	 * @param string $id_base - e.g. 'meta'
	 * @return bool
	 */
	public function _save_widget( $id_base ) {
		global $wp_registered_widget_updates;

		$control = $wp_registered_widget_updates[$id_base];

		if ( is_callable( $control['callback'] ) ) {

			call_user_func_array( $control['callback'], $control['params'] );

			return true;
		}

		return false;

	}


	/**
	 * Adds a widget to WordPress. First creates a new widget instance, then
	 * adds the widget instance to the mega menu widget sidebar area.
	 *
	 * @since 1.0.0
	 * @param string $id_base
	 * @param int $menu_item_id
	 * @param string $title
	 */
	public function _add_widget( $id_base, $menu_item_id, $title, $total_cols ) {

		require_once( ABSPATH . 'wp-admin/includes/widgets.php' );

		$next_id = next_widget_id_number( $id_base );

		$this->add_widget_instance( $id_base, $next_id, $menu_item_id );

		$widget_id = $this->add_widget_to_sidebar( $id_base, $next_id );

		$return = $this->get_widget_html( $title, $widget_id, 4, $total_cols );

		return array( 'content' => $return, 'id' => $widget_id );

	}

	/**
	 * Get single widget HTML markup to pass into item manager
	 *
	 * @since  1.0.0
	 * @param  string  $title      widget title
	 * @param  string  $id         widget ID
	 * @param  integer $cols       current columns count
	 * @param  integer $total_cols total avaliable columns count
	 * @return [type]              widget HTML
	 */
	public function get_widget_html( $title, $widget_id, $cols, $total_cols ) {

		$return  = '<div class="widget" id="' . esc_attr( $widget_id ) . '" data-columns="' . esc_attr( $cols ) . '" data-total-columns="' . esc_attr( $total_cols ) . '" data-widget-id="' . esc_attr( $widget_id ) . '">';
		$return .=     '<div class="widget-top">';
		$return .=         '<div class="widget-title-action">';
		$return .=             '<a class="widget-option widget-contract widget-resize" data-action="contract">-</a>';
		$return .=             '<span class="widget-cols-counter"><b>' . esc_attr( $cols ) . '</b>/<i>' . esc_attr( $total_cols ) . '</i></span>';
		$return .=             '<a class="widget-option widget-expand widget-resize" data-action="expand">+</a>';
		$return .=             '<a class="widget-option widget-edit"></a>';
		$return .=         '</div>';
		$return .=         '<div class="widget-title">';
		$return .=             '<h4>' . $title . '</h4>';
		$return .=             '<span class="spinner" style="display: none;"></span>';
		$return .=         '</div>';
		$return .=         '</div>';
		$return .=     '<div class="widget-inner"></div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 * Adds a new widget instance of the specified base ID to the database.
	 *
	 * @since 1.0.0
	 * @param string $id_base
	 * @param int $next_id
	 * @param int $menu_item_id
	 */
	private function add_widget_instance( $id_base, $next_id, $menu_item_id ) {

		$current_widgets = get_option( 'widget_' . $id_base );

		$current_widgets[ $next_id ] = array(
			"mega_menu_columns"        => 4,
			"mega_menu_parent_menu_id" => $menu_item_id
		);

		update_option( 'widget_' . $id_base, $current_widgets );

	}

	/**
	 * Removes a widget instance from the database
	 *
	 * @since 1.0.0
	 * @param string $widget_id e.g. meta-3
	 * @return bool. True if widget has been deleted.
	 */
	private function remove_widget_instance( $widget_id ) {

		$widget_data   = $this->parse_widget_id( $widget_id );
		$id_base       = $widget_data['id_base'];
		$widget_number = $widget_data['id'];

		// add blank widget
		$current_widgets = get_option( 'widget_' . $id_base );

		if ( isset( $current_widgets[ $widget_number ] ) ) {

			unset( $current_widgets[ $widget_number ] );

			update_option( 'widget_' . $id_base, $current_widgets );

			return true;

		}

		return false;

	}


	/**
	 * Updates the number of mega columns for a specified widget.
	 *
	 * @since 1.0.0
	 * @param string $widget_id
	 * @param int $columns
	 */
	public function _update_columns( $widget_id, $columns ) {

		$widget_data   = $this->parse_widget_id( $widget_id );
		$id_base       = $widget_data['id_base'];
		$widget_number = $widget_data['id'];

		$current_widgets = get_option( 'widget_' . $id_base );

		$current_widgets[ $widget_number ]["mega_menu_columns"] = $columns;

		update_option( 'widget_' . $id_base, $current_widgets );

		return true;

	}


	/**
	 * Deletes a widget from WordPress
	 *
	 * @since 1.0.0
	 * @param string $widget_id e.g. meta-3
	 */
	public function _delete_widget( $widget_id ) {

		$this->remove_widget_from_sidebar( $widget_id );
		$this->remove_widget_instance( $widget_id );

		return true;

	}


	/**
	 * Moves a widget from one position to another. The widgets are stored as an ordered
	 * array in the database.
	 *
	 * @since 1.0.0
	 * @param string $widget_to_move
	 * @param int $new_widget_position. Zero based index.
	 * @return string $widget_id. The widget that has been moved.
	 */
	public function _move_widget( $widget_to_move, $new_widget_position, $menu_item_id ) {

		// $new_widget_position assumes that all widgets belong to this Menu ID,
		// but widgets are stored in this area for _all_ Menu IDs.
		// Work out the new widget position taking into account that other menu IDs
		// also store their widgets here
		$menu_widgets = array();
		$non_menu_widgets = array();

		if ( $mega_menu_widgets = $this->get_sidebar_widgets() ) {

			foreach ( $mega_menu_widgets as $widget_id ) {

				$settings = $this->get_settings_for_widget_id( $widget_id );

				// split our widgets into arrays that belong with this menu ID, and ones that don't
				if ( $settings['mega_menu_parent_menu_id'] == $menu_item_id ) {

					$menu_widgets[] = $widget_id;

				} else {

					$non_menu_widgets[] = $widget_id;

				}

			}

			// find the old position of the widget
			$old_widget_position = array_search( $widget_to_move, $menu_widgets );

			// move widget from old position to new position
			$out = array_splice( $menu_widgets, $old_widget_position, 1 );
			array_splice( $menu_widgets, $new_widget_position, 0, $out );

			// merge back together the menu and non menu widgets
			$mega_menu_widgets = array_merge( $non_menu_widgets, $menu_widgets );

			$this->set_sidebar_widgets( $mega_menu_widgets );

		}

		return $mega_menu_widgets;

	}


	/**
	 * Adds a widget to the Mega Menu widget sidebar
	 *
	 * @since 1.0.0
	 */
	private function add_widget_to_sidebar( $id_base, $next_id ) {

		$widget_id = $id_base . '-' . $next_id;

		$sidebar_widgets = $this->get_sidebar_widgets();

		$sidebar_widgets[] = $widget_id;

		$this->set_sidebar_widgets($sidebar_widgets);

		return $widget_id;

	}


	/**
	 * Removes a widget from the Mega Menu widget sidebar
	 *
	 * @since 1.0.0
	 * @return string The widget that was removed
	 */
	private function remove_widget_from_sidebar($widget_id) {

		$widgets = $this->get_sidebar_widgets();

		$new_mega_menu_widgets = array();

		foreach ( $widgets as $widget ) {

			if ( $widget != $widget_id )
				$new_mega_menu_widgets[] = $widget;

		}

		$this->set_sidebar_widgets($new_mega_menu_widgets);

		return $widget_id;

	}


	/**
	 * Returns an unfiltered array of all widgets in our sidebar
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_sidebar_widgets() {

		$sidebar_widgets = wp_get_sidebars_widgets();

		if ( ! isset( $sidebar_widgets[ 'cherry-mega-menu'] ) ) {
			return false;
		}

		return $sidebar_widgets[ 'cherry-mega-menu' ];

	}


	/**
	 * Sets the sidebar widgets
	 *
	 * @since 1.0.0
	 */
	private function set_sidebar_widgets( $widgets ) {

		$sidebar_widgets = wp_get_sidebars_widgets();

		$sidebar_widgets[ 'cherry-mega-menu' ] = $widgets;

		wp_set_sidebars_widgets( $sidebar_widgets );

	}

}

endif;