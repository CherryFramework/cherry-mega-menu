<?php
/**
 * Cherry mega menu walker class
 *
 * @package   cherry_mega_menu
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if( ! class_exists( 'cherry_mega_menu_walker' ) ) :

/**
 * @package WordPress
 * @since 1.0.0
 * @uses Walker
 */
class cherry_mega_menu_walker extends Walker_Nav_Menu {

	/**
	 * Check if sub grouped to cols
	 * @var boolean
	 */
	private $child_columns = false;

	/**
	 * Mega submenu trigger
	 * @var boolean
	 */
	private $is_mega_sub = false;

	/**
	 * Starts the list before the elements are added.
	 *
	 * @see Walker::start_lvl()
	 *
	 * @since 1.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 */
	function start_lvl( &$output, $depth = 0, $args = array() ) {

		$indent   = str_repeat("\t", $depth);
		$classes  = 'cherry-mega-menu-sub level-' . $depth;
		$effect   = cherry_mega_menu_get_option( 'mega-menu-effect', 'slide-top' );
		$classes .= ' effect-' . $effect;

		if ( true === $this->is_mega_sub ) {
			$classes .= ' mega-sub';
		} else {
			$classes .= ' simple-sub';
		}


		$output .= "\n$indent<ul class=\"$classes\">\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @see Walker::end_lvl()
	 *
	 * @since 1.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 */
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	/**
	 * Start the element output.
	 *
	 * @see Walker::start_el()
	 *
	 * @since 1.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item   Menu item data object.
	 * @param int    $depth  Depth of menu item. Used for padding.
	 * @param array  $args   An array of arguments. @see wp_nav_menu()
	 * @param int    $id     Current item ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$mega_settings = isset( $item->megamenu_settings ) ? $item->megamenu_settings : array();

		if ( ! isset( $item->description ) ) {
			$item->description = false;
		}

		$mega_settings = wp_parse_args( $mega_settings, array(
			'align'                 => 'top-left',
			'type'                  => '',
			'item-hide-text'        => '',
			'item-hide-arrow'       => '',
			'item-icon'             => '',
			'item-arrow'            => '',
			'sub-items-to-cols'     => '',
			'sub-cols-num'          => '4',
			'item-submenu-position' => 'fullwidth',
			'item-width-fullscreen' => '100%',
			'item-width-desktop'    => '100%',
			'item-width-tablet'     => '100%',
			'item-hide-mobile'      => ''
		) );

		if ( 0 === $depth && 'sub-items-to-cols' == $mega_settings['sub-items-to-cols'] ) {
			$this->child_columns = $mega_settings['sub-cols-num'];
		} elseif ( 0 === $depth && 'sub-items-to-cols' != $mega_settings['sub-items-to-cols'] ) {
			$this->child_columns = '';
		}

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		if ( $depth == 0 ) {
			$classes[] = 'cherry-mega-menu-top-item';
			$classes[] = 'item-submenu-position-' . $mega_settings['item-submenu-position'];
		} else {
			$classes[] = 'cherry-mega-menu-sub-item';
		}

		if ( $this->has_children ) {
			$classes[] = 'cherry-mega-menu-has-children';
		}

		if ( $depth == 0 && 'megamenu' == $mega_settings['type'] && $this->has_children ) {
			$classes[] = 'item-type-megamenu';
			$this->is_mega_sub = true;
		}

		if ( 'megamenu' != $mega_settings['type'] && $this->has_children ) {
			$classes[] = 'item-type-standard';
			$this->is_mega_sub = false;
		}

		if ( isset( $mega_settings['align']) && $mega_settings['align'] != 'left' && $depth == 0 ) {
			$classes[] = 'item-align-' . $mega_settings['align'];
		}

		if ( $depth > 0 ) {
			$classes[] = 'item-nested-sub';
			$classes[] = 'item-nested-sub-' . $depth;
		}

		if ( 1 === $depth && $this->child_columns ) {
			$classes[] = 'menu-columns-' . round( 12 / $this->child_columns, 0 );
			$classes[] = 'sub-column-title';
		}

		if ( 1 < $depth && $this->child_columns ) {
			$classes[] = 'sub-column-item';
		}

		if ( isset( $item->type ) && 'widget' == $item->type ) {
			$classes[] = 'menu-item-widget';
		} else {
			$classes[] = 'menu-item-standard';
		}

		if ( in_array( 'current_page_item', $classes ) || in_array( 'current-menu-item', $classes ) ) {
			wp_cache_set( 'cherry_menu_has_active', 'has', 'cherry' );
		}

		/**
		 * Default WP filter
		 *
		 * Filter the CSS class(es) applied to a menu item's list item element.
		 *
		 * @since 3.0.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param array  $classes The CSS classes that are applied to the menu item's `<li>` element.
		 * @param object $item    The current menu item.
		 * @param array  $args    An array of {@see wp_nav_menu()} arguments.
		 * @param int    $depth   Depth of menu item. Used for padding.
		 */
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		/**
		 * Default WP filter
		 *
		 * Filter the ID applied to a menu item's list item element.
		 *
		 * @since 3.0.1
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param string $menu_id The ID that is applied to the menu item's `<li>` element.
		 * @param object $item    The current menu item.
		 * @param array  $args    An array of {@see wp_nav_menu()} arguments.
		 * @param int    $depth   Depth of menu item. Used for padding.
		 */
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$meta_atts = '';

		$hr_position = !in_array( $mega_settings['item-submenu-position'], array( 'vertical-full', 'vertical-parent' ) ) ? $mega_settings['item-submenu-position'] : 'fullwidth';
		$vr_position = in_array( $mega_settings['item-submenu-position'], array( 'vertical-full', 'vertical-parent' ) ) ? $mega_settings['item-submenu-position'] : 'vertical-parent';

		if ( $depth == 0 && $this->has_children && 'megamenu' == $mega_settings['type'] ) {
			$meta_atts = array(
				'data-width-fullscreen' => cherry_mega_menu_sanitize_width( $mega_settings['item-width-fullscreen'] ),
				'data-width-desktop'    => cherry_mega_menu_sanitize_width( $mega_settings['item-width-desktop'] ),
				'data-width-tablet'     => cherry_mega_menu_sanitize_width( $mega_settings['item-width-tablet'] ),
				'data-hide-mobile'      => $mega_settings['item-hide-mobile'],
				'data-sub-hr-position'  => $hr_position,
				'data-sub-vr-position'  => $vr_position,
				'data-sub-type'         => $mega_settings['type']
			);
		} elseif ( $depth == 0 && $this->has_children && 'megamenu' != $mega_settings['type'] ) {
			$meta_atts = array(
				'data-hide-mobile'      => $mega_settings['item-hide-mobile'],
				'data-sub-hr-position'  => $hr_position,
				'data-sub-vr-position'  => $vr_position,
				'data-sub-type'         => 'standard'
			);
		}

		/**
		 * Filter additional attributes for mega menu item
		 *
		 * @since 1.0.0
		 *
		 * @param array  $meta_atts default attributes
		 * @param object $item      The current menu item.
		 * @param array  $args      An array of {@see wp_nav_menu()} arguments.
		 * @param int    $depth     Depth of menu item. Used for padding.
		 */
		$meta_atts = apply_filters( 'cherry_mega_menu_additional_item_attributes', $meta_atts, $item, $args, $depth );

		$meta_atts = cherry_mega_menu_parse_atts( $meta_atts );

		$output .= $indent . '<li' . $id . $class_names . $meta_atts . '>';

		// output the widgets
		if ( $item->content ) {

			$item_output = $item->content;

			/**
			 * Default WP filter
			 *
			 * Filter a menu item's starting output.
			 *
			 * The menu item's starting output only includes `$args->before`, the opening `<a>`,
			 * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
			 * no filter for modifying the opening and closing `<li>` for a menu item.
			 *
			 * @since 3.0.0
			 *
			 * @param string $item_output The menu item's starting HTML output.
			 * @param object $item        Menu item data object.
			 * @param int    $depth       Depth of menu item. Used for padding.
			 * @param array  $args        An array of {@see wp_nav_menu()} arguments.
			 */
			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );

			return;
		}

		/** This filter is documented in wp-includes/post-template.php */
		$link_title = apply_filters( 'the_title', $item->title, $item->ID );

		$atts = array();
		$atts['title']      = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target']     = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']        = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']       = ! empty( $item->url )        ? $item->url        : '';
		$atts['data-title'] = $link_title;

		/**
		 * Default WP filter
		 *
		 * Filter the HTML attributes applied to a menu item's anchor element.
		 *
		 * @since 3.6.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param array $atts {
		 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
		 *
		 *     @type string $title  Title attribute.
		 *     @type string $target Target attribute.
		 *     @type string $rel    The rel attribute.
		 *     @type string $href   The href attribute.
		 * }
		 * @param object $item  The current menu item.
		 * @param array  $args  An array of {@see wp_nav_menu()} arguments.
		 * @param int    $depth Depth of menu item. Used for padding.
		 */
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$item_output = $args->before;

		$item_output .= '<a'. $attributes .'>';

		$link_before = $args->link_before;

		if ( $mega_settings['item-icon'] ) {
			/**
			 * Filter menu item icon HTML format
			 *
			 * @since  1.0.0
			 *
			 * @param  string  default FontAwesome icon format
			 */
			$icon_format = apply_filters( 'cherry_mega_menu_icon_format', '<i class="fa %1$s mega-menu-icon"></i>' );
			$link_before .= sprintf( $icon_format, esc_attr( $mega_settings['item-icon'] ) );
		}

		/**
		 * Filter HTML outputed before link text. By default appends menu icon, if exist
		 *
		 * @since  1.0.0
		 *
		 * @param string  $link_before  default HTML markup before link text
		 * @param object  $item         The current menu item.
		 * @param array   $args         An array of {@see wp_nav_menu()} arguments.
		 * @param int     $depth        Depth of menu item. Used for padding.
		 */
		$item_output .= apply_filters( 'cherry_mega_menu_before_link_text', $link_before, $item, $args, $depth );

		if ( ! $mega_settings['item-hide-text'] ) {
			$item_output .= $link_title;
		}

		$link_after = $args->link_after;

		if ( $mega_settings['item-arrow'] && $this->has_children && ! $mega_settings['item-hide-arrow'] ) {

			/**
			 * Filter menu item arrow HTML format
			 *
			 * @since  1.0.0
			 *
			 * @param  string  default FontAwesome icon format
			 */
			$icon_format = apply_filters( 'cherry_mega_menu_arrow_format', '<i class="fa %1$s mega-menu-arrow %2$s"></i>' );
			$arrow_level = ( $depth > 0 ) ? 'sub-arrow' : 'top-level-arrow';
			$link_after  = sprintf( $icon_format, esc_attr( $mega_settings['item-arrow'] ), $arrow_level ) . $link_after;

		} elseif ( $this->has_children && $depth == 0 && ! $mega_settings['item-hide-arrow'] ) {

			/**
			 * Filter default submenu arrow indicator for top level item
			 *
			 * @since  1.0.0
			 *
			 * @param  string        Default arrow HTML
			 * @param  object $item  The current menu item.
			 * @param  array  $args  An array of {@see wp_nav_menu()} arguments.
			 * @param  int    $depth Depth of menu item. Used for padding.
			 */
			$default_arrow = apply_filters( 'cherry_mega_menu_default_arrow_top_level', '<i class="fa fa-angle-down mega-menu-arrow top-level-arrow"></i>', $item, $args, $depth );
			$link_after    = $default_arrow . $link_after;

		} elseif ( $this->has_children && $depth > 0 && ! $mega_settings['item-hide-arrow'] ) {

			/**
			 * Filter default submenu arrow indicator for nested level item
			 *
			 * @since  1.0.0
			 *
			 * @param  string        Default arrow HTML
			 * @param  object $item  The current menu item.
			 * @param  array  $args  An array of {@see wp_nav_menu()} arguments.
			 * @param  int    $depth Depth of menu item. Used for padding.
			 */
			$default_arrow = apply_filters( 'cherry_mega_menu_default_arrow_nested_level', '<i class="fa fa-angle-right mega-menu-arrow sub-arrow"></i>', $item, $args, $depth );
			$link_after    = $default_arrow . $link_after;

		}

		/**
		 * Filter HTML outputed before link text. By default appends menu icon, if exist
		 *
		 * @since  1.0.0
		 *
		 * @param string  $link_after   default HTML markup after link text
		 * @param object  $item         The current menu item.
		 * @param array   $args         An array of {@see wp_nav_menu()} arguments.
		 * @param int     $depth        Depth of menu item. Used for padding.
		 */
		$item_output .= apply_filters( 'cherry_mega_menu_after_link_text', $link_after, $item, $args, $depth );

		$item_output .= '</a>';

		$item_output .= $args->after;

		/**
		 * Default WP filter
		 *
		 * Filter a menu item's starting output.
		 *
		 * The menu item's starting output only includes `$args->before`, the opening `<a>`,
		 * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
		 * no filter for modifying the opening and closing `<li>` for a menu item.
		 *
		 * @since 3.0.0
		 *
		 * @param string $item_output The menu item's starting HTML output.
		 * @param object $item        Menu item data object.
		 * @param int    $depth       Depth of menu item. Used for padding.
		 * @param array  $args        An array of {@see wp_nav_menu()} arguments.
		 */
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

}

endif;