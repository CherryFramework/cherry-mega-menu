<?php
/**
 * Cherry mega menu core functions
 * 
 * @package   cherry_mega_menu
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

/**
 * Get option by name from theme options
 *
 * @since  1.0.0
 *
 * @uses   cherry_get_option  use cherry_get_option from Cherry framework if exist
 * 
 * @param  string  $name    option name to get
 * @param  mixed   $default default option value
 * @return mixed            option value
 */
function cherry_mega_menu_get_option( $name, $default = false ) {

	if ( function_exists( 'cherry_get_option' ) ) {
		$result = cherry_get_option( $name, $default );
		return $result;
	}

	return $default;
}

/**
 * Parse HTML tag attributes array into formatted string
 *
 * @since  1.0.0
 * 
 * @param  array  $atts arry of attributes
 * @return string       parsed string
 */
function cherry_mega_menu_parse_atts( $atts = array() ) {

	if ( !is_array( $atts ) ) {
		return;
	}

	$str_atts = '';

	foreach ( $atts as $att_name => $att_value ) {
		$str_atts .= sprintf( ' %1$s="%2$s"', esc_attr( $att_name ), esc_attr( $att_value ) );
	}

	return $str_atts;
}

/**
 * Sanitize width value for % or 'px' format
 *
 * @since  1.0.0
 * 
 * @param  string  $value  input value
 * @return string          sanitized value
 */
function cherry_mega_menu_sanitize_width( $value ) {

	if ( strpos( $value, '%' ) ) {
		$value = cherry_mega_menu_sanitize_percent_width( $value );
		return $value;
	}

	$value = filter_var( $value, FILTER_SANITIZE_NUMBER_INT );
	$value = absint( $value );
	return $value . 'px';
}

/**
 * Sanitize width value for % format
 *
 * @since  1.0.0
 * 
 * @param  string  $value  input value
 * @return string          sanitized value
 */
function cherry_mega_menu_sanitize_percent_width( $value ) {
	
	$value = filter_var( $value, FILTER_SANITIZE_NUMBER_INT );
	$value = absint( $value );

	if ( $value > 100 ) {
		$value = 100;
	}

	return $value . '%';
}

/**
 * Get admin UI wrapper CSS class
 *
 * @since  1.0.0
 */
function cherry_mega_menu_ui_class( $classes = array(), $delimiter = ' ' ) {

	// prevent PHP errors
	if ( ! $classes || ! is_array( $classes ) ) {
		$classes = array();
	}
	if ( ! $delimiter || ! is_string( $delimiter ) ) {
		$delimiter = ' ';
	}

	$classes = array_merge( array( 'cherry-ui-core' ), $classes );

	/**
	 * Filter UI wrapper CSS classes
	 * 
	 * @since 1.0.0
	 *
	 * @param array $classes - default CSS classes array
	 */
	$classes = apply_filters( 'cherry_ui_wrapper_class', $classes );

	$classes = array_unique( $classes );

	return join( $delimiter, $classes );

}