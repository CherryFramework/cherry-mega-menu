<?php
/**
 * Add cherry mega menu options
 *
 * @package   cherry_mega_menu
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

if ( ! class_exists( 'cherry_mega_menu_options' ) ) {

	/**
	 * cherry mega menu options management class
	 *
	 * @since  1.0.0
	 */
	class cherry_mega_menu_options {

		/**
		 * build plugin instance
		 */
		public function __construct() {

			add_filter( 'cherry_defaults_settings', array( $this, 'add_options') );
			add_filter( 'cherry_optimization_options_list', array( $this, 'add_optimization_options') );

		}

		/**
		 * Add mega menu options
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $sections  default sections array
		 * @return array             filtered sections array
		 */
		public function add_options( $sections ) {

			// Get menus
			$menus = get_registered_nav_menus();

			$options_menus = array(
				'0' => __( 'Select main theme menu', 'cherry-mega-menu' )
			);

			$options_menus = array_merge( $options_menus, $menus );

			$menu_options = array(
				'mega-menu-location' => array(
					'type'			=> 'select',
					'title'			=> __( 'Main theme menu location', 'cherry-mega-menu' ),
					'label'			=> __( 'Select the menu location for your main menu', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'      	=>  array(
						'type'		=> 'text',
						'content'	=> __( 'Select menu location for main theme menu. Mega menu will be applied to this location.', 'cherry-mega-menu' )
					),
					'multiple' => true,
					'value'    => array( 'primary' ),
					'class'    => 'cherry-multi-select',
					'options'  => $options_menus,
				),
				'mega-menu-enabled' => array(
					'type'			=> 'switcher',
					'title'			=> __( 'Mega menu enabled', 'cherry-mega-menu' ),
					'label'			=> __( 'Enable / Disable', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'      	=>  array(
						'type'		=> 'text',
						'content'	=> __( 'This applies only to main site menu', 'cherry-mega-menu' )
					),
					'value'			=> 'true',
					'default_value'	=> 'true'
				),
				'mega-menu-mobile-trigger' => array(
					'type'			=> 'slider',
					'title'			=> __( 'Mobile starts from', 'cherry-mega-menu' ),
					'label'			=> __( 'Select the window dimensions for mobile menu layout switching.', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'			=>  array(
						'type'		=> 'text',
						'content'	=> __( 'Select the window dimensions for mobile menu layout switching.', 'cherry-mega-menu' )
					),
					'max_value'		=> 1200,
					'min_value'		=> 480,
					'value'			=> 768
				),
				'mega-menu-mobile-label' => array(
					'type'			=> 'text',
					'title'			=> __( 'Mobile nav label', 'cherry-mega-menu' ),
					'label'			=> __( 'Enter the mobile navigation label ', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'      	=>  array(
						'type'		=> 'text',
						'content'	=> __( 'Enter the mobile navigation label ', 'cherry-mega-menu' )
					),
					'value'			=> __( 'Menu', 'cherry-mega-menu' ),
					'default_value'	=> __( 'Menu', 'cherry-mega-menu' )
				),
				'mega-menu-direction' => array(
					'type'			=> 'select',
					'title'			=> __( 'Menu direction', 'cherry-mega-menu' ),
					'label'			=> __( 'Select your direction: vertical or horizontal', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'      	=>  array(
						'type'		=> 'text',
						'content'	=> __( 'Select your direction: vertical or horizontal', 'cherry-mega-menu' )
					),
					'value'	        => 'horizontal',
					'class'			=> 'width-full',
					'options'		=> array(
						'horizontal' => __( 'Horizontal', 'cherry-mega-menu' ),
						'vertical'   => __( 'Vertical', 'cherry-mega-menu' )
					)
				),
				'mega-menu-effect' => array(
					'type'			=> 'select',
					'title'			=> __( 'Animation effect', 'cherry-mega-menu' ),
					'label'			=> __( 'Animation effects for the dropdown menu', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'      	=>  array(
						'type'		=> 'text',
						'content'	=> __( 'Animation effects for the dropdown menu', 'cherry-mega-menu' )
					),
					'value'	        => 'slide-top',
					'class'			=> 'width-full',
					'options'		=> array(
						'fade-in'      => __( 'Fade In', 'cherry-mega-menu' ),
						'slide-top'    => __( 'Slide from top', 'cherry-mega-menu' ),
						'slide-bottom' => __( 'Slide from bottom', 'cherry-mega-menu' ),
						'slide-left'   => __( 'Slide from left', 'cherry-mega-menu' ),
						'slide-right'  => __( 'Slide from right', 'cherry-mega-menu' )
					)
				),
				'mega-menu-event' => array(
					'type'			=> 'select',
					'title'			=> __( 'Event', 'cherry-mega-menu' ),
					'label'			=> __( 'Select an activation event', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'      	=>  array(
						'type'		=> 'text',
						'content'	=> __( 'Select an activation event', 'cherry-mega-menu' )
					),
					'value'	        => 'hover',
					'class'			=> 'width-full',
					'options'		=> array(
						'hover' => __( 'Hover', 'cherry-mega-menu' ),
						'click' => __( 'Click', 'cherry-mega-menu' )
					)
				),
				'mega-menu-parent-container' => array(
					'type'			=> 'text',
					'title'			=> __( 'Menu parent container CSS selector', 'cherry-mega-menu' ),
					'label'			=> __( 'Enter CSS selector name for mega menu parent container (if needed)', 'cherry-mega-menu' ),
					'decsription'	=> '',
					'hint'      	=>  array(
						'type'		=> 'text',
						'content'	=> __( 'Enter CSS class name for mega menu parent container (if needed)', 'cherry-mega-menu' )
					),
					'value'			=> apply_filters( 'cherry_mega_menu_default_parent', '.cherry-mega-menu' ),
				)
			);

			$menu_options = apply_filters( 'cherry_mega_menu_options', $menu_options );

			$sections['mega-menu-options-section'] = array(
				'name'         => 'Megamenu',
				'icon'         => 'dashicons dashicons-arrow-right',
				'parent'       => 'navigation-section',
				'priority'     => 41,
				'options-list' => $menu_options
			);

			return $sections;
		}

		/**
		 * Add mega menu caching to optimization options
		 * @param array $options optimization options array
		 */
		function add_optimization_options( $options ) {

			$options['mega-menu-cache'] = array(
				'type'			=> 'switcher',
				'title'			=> __( 'Mega menu caching enabled', 'cherry-mega-menu' ),
				'label'			=> __( 'Enable / Disable', 'cherry-mega-menu' ),
				'decsription'	=> '',
				'hint'      	=>  array(
					'type'		=> 'text',
					'content'	=> __( 'Enable caching for mega menu items', 'cherry-mega-menu' )
				),
				'value'			=> 'false',
			);

			return $options;
		}

	}

	new cherry_mega_menu_options();

}