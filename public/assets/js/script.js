/*
 * debouncedresize: special jQuery event that happens once after a window resize
 *
 * latest version and complete README available on Github:
 * https://github.com/louisremi/jquery-smartresize
 *
 * Copyright 2012 @louis_remi
 * Licensed under the MIT license.
 */
( function( $ ) {

	'use strict';

	var $event = $.event,
		$special,
		resizeTimeout;

	$special = $event.special.debouncedresize = {
		setup: function() {
			$( this ).on( 'resize', $special.handler );
		},
		teardown: function() {
			$( this ).off( 'resize', $special.handler );
		},
		handler: function( event, execAsap ) {

			// Save the context
			var context = this,
				args = arguments,
				dispatch = function() {

					// Set correct event type
					event.type = 'debouncedresize';
					$event.dispatch.apply( context, args );
				};

			if ( resizeTimeout ) {
				clearTimeout( resizeTimeout );
			}

			if ( execAsap ) {
				dispatch();
			} else {
				resizeTimeout = setTimeout( dispatch, $special.threshold );
			}
		},
		threshold: 150
	};
} )( jQuery );

// Mega Menu jQuery Plugin
( function( $ ) {

	'use strict';

	$.fn.megaMenu = function( options ) {
		var menu = $( this ),
			durationTimeout,
			triggerFullscreen = 1200,
			triggerDesktop = 970,
			isMobile = false,
			isTouchDevice,
			switchMobile,
			mobileOn,
			mobileOff,
			closePanels,
			hidePanel,
			showPanel,
			openOnClick,
			openOnHover,
			isInMegamenu,
			getMenuWidth,
			panelStylesGenerator,
			init;

		menu.settings = $.extend( {
			event: menu.data( 'event' ),
			effect: menu.data( 'effect' ),
			mobile: menu.data( 'mobile-trigger' ),
			parent: menu.data( 'parent-selector' ),
			direction: menu.data( 'direction' )
		}, options );

		isTouchDevice = function() {
			return ( 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0 );
		};

		isInMegamenu = function( el ) {

			if ( el.parents( 'li.item-type-megamenu' ).length ) {
				return true;
			}

			return false;
		};

		switchMobile = function() {
			var width = window.innerWidth;

			if ( width <= menu.settings.mobile ) {
				mobileOn();
			} else {
				mobileOff();
			}
		};

		mobileOn = function() {
			var hideThis;

			menu.addClass( 'mega-menu-mobile-on' ).css( 'display', 'none' ).siblings( '.cherry-mega-menu-mobile-trigger' ).addClass( 'mega-menu-mobile-on' );
			menu.find( '.cherry-mega-menu-sub' ).each( function() {
				$( this ).css( 'display', 'none' );
			});
			menu.find( '.cherry-mega-menu-has-children' ).each( function() {
				hideThis = $( this ).data( 'hide-mobile' );

				$( this ).addClass( hideThis );
			});
			isMobile = true;
		};

		mobileOff = function() {
			var hideThis;

			menu.removeClass( 'mega-menu-mobile-on' ).css( 'display', 'block' ).siblings( '.cherry-mega-menu-mobile-trigger' ).removeClass( 'mega-menu-mobile-on' );
			menu.find( '.cherry-mega-menu-sub' ).each( function() {
				$( this ).css( 'display', 'block' );
			});
			menu.find( '.cherry-mega-menu-has-children' ).each( function() {
				hideThis = $( this ).data( 'hide-mobile' );
				$( this ).removeClass( hideThis );
			});
			isMobile = false;
		};

		getMenuWidth = function( stringWidth ) {
			var width;

			if ( stringWidth.indexOf( '%' ) >= 0 ) {
				width = $( menu.settings.parent ).width();

				width = ( width * parseInt( stringWidth, 10 ) ) / 100;

				return width;
			}

			if ( stringWidth.indexOf( 'px' ) >= 0 ) {
				width = parseInt( stringWidth, 10 );

				return width;
			}

			return 0;
		};

		closePanels = function() {
			$( '.mega-toggle-on > a', menu ).each( function() {
				hidePanel( $( this ), true );
			});
		};

		hidePanel = function( anchor ) {
			anchor.parent().removeClass( 'mega-toggle-on cherry-mega-menu-hover' ).triggerHandler( 'close_panel' );
			anchor.siblings( '.cherry-mega-menu-sub' ).removeClass( 'active-sub' ).addClass( 'in-transition' );

			clearTimeout( durationTimeout );
			durationTimeout = setTimeout(
				function() {
					anchor.siblings( '.cherry-mega-menu-sub' ).removeClass( 'in-transition' );
				},
				window.cherry_mega_menu_data.duration
			);

			if ( isMobile ) {
				anchor.siblings( '.cherry-mega-menu-sub' ).slideUp( 'fast' );
			}
		};

		showPanel = function( anchor ) {

			// All open children of open siblings
			var item = anchor.parent(),
				menuWidth = '100%',
				type = item.data( 'sub-type' ),
				widthFullscreen = item.data( 'width-fullscreen' ),
				widthDesktop = item.data( 'width-desktop' ),
				widthTablet = item.data( 'width-tablet' ),
				windowWidth = $( document ).width(),
				hrPosition = item.data( 'sub-hr-position' ),
				vrPosition = item.data( 'sub-vr-position' ),
				position = hrPosition,
				styles = {};

			item.addClass( 'cherry-mega-menu-hover' )
				.siblings()
				.find( '.mega-toggle-on' )
				.addBack( '.mega-toggle-on' )
				.children( 'a' )
				.each( function() {
					hidePanel( $( this ), true );
				} );

			if ( 'vertical' === menu.settings.direction ) {
				position = vrPosition;
			}

			if ( 'fullwidth' !== position && windowWidth >= triggerFullscreen ) {
				menuWidth = widthFullscreen;
			} else if ( 'fullwidth' !== position && triggerDesktop <= windowWidth && windowWidth < triggerFullscreen ) {
				menuWidth = widthDesktop;
			} else if ( 'fullwidth' !== position && windowWidth < triggerDesktop ) {
				menuWidth = widthTablet;
			}

			styles = panelStylesGenerator( item, position, type, menuWidth );

			item.addClass( 'mega-toggle-on' ).triggerHandler( 'open_panel' );

			anchor.siblings( '.cherry-mega-menu-sub' ).css( styles ).addClass( 'active-sub' );

			if ( true === isMobile ) {
				anchor.siblings( '.cherry-mega-menu-sub' ).slideDown( 'fast' );
			}
		};

		panelStylesGenerator = function( item, position, type, menuWidth ) {
			var styles = {},
				width,
				left,
				right,
				top,
				indent;

			if ( 'standard' === type ) {
				menuWidth = null;
			}

			switch ( position ) {
				case 'fullwidth':
					menuWidth = getMenuWidth( menuWidth );
					indent = menuWidth / 2;
					left = $( menu.settings.parent ).offset().left - menu.offset().left + parseInt( $( menu.settings.parent ).css( 'padding-left' ), 10 ) + parseInt( $( menu.settings.parent ).css( 'border-left-width' ), 10 );
					styles.left = left + 'px';
					break;

				case 'center':
					width = getMenuWidth( menuWidth );
					indent = width / 2;

					styles.left = '50%';
					styles['margin-left'] = '-' + indent + 'px';
					break;

				case 'left-container':
					styles.left = '0';
					styles.right = 'auto';
					break;

				case 'right-container':
					styles.left = 'auto';
					styles.right = '0';
					break;

				case 'left-parent':
					left = item.position().left;
					styles.left = left + 'px';
					styles.right = 'auto';

					if ( '100%' === menuWidth && left > 0 ) {
						menuWidth = 'auto';
						styles.right = '0';
					}
					break;

				case 'right-parent':
					right = menu.offset().left + parseInt( menu.css( 'border-left-width' ), 10 ) + menu.width() - item.offset().left - item.width();
					styles.left = 'auto';
					styles.right = right + 'px';

					if ( '100%' === menuWidth && right > 0 ) {
						menuWidth = 'auto';
						styles.left = '0';
					}
					break;

				case 'vertical-full':
					top = 0;

					if ( 'standard' === type ) {
						top = $( menu.settings.parent ).offset().top - item.offset().top;
					}

					styles.top = top + 'px';
					styles.left = '100%';
					styles.right = 'auto';
					break;

				case 'vertical-parent':
					top = item.offset().top - menu.offset().top;

					if ( 'standard' === type ) {
						top = 0;
					}
					styles.top = top + 'px';
					styles.left = '100%';
					styles.right = 'auto';
					break;
			}

			if ( undefined !== menuWidth && 'vertical' === menu.settings.direction && menuWidth.indexOf( '%' ) >= 0 ) {
				menuWidth = 'auto';
			}

			if ( null !== menuWidth ) {
				styles.width = menuWidth;
			}

			styles['-webkit-transition-duration'] = window.cherry_mega_menu_data.duration + 'ms';
			styles['-moz-transition-duration'] = window.cherry_mega_menu_data.duration + 'ms';
			styles['transition-duration'] = window.cherry_mega_menu_data.duration + 'ms';

			return styles;
		};

		openOnClick = function() {

			var currentItem;

			// Hide menu when clicked away from
			$( document ).on( 'click touchstart', function( event ) {

				if ( ! $( event.target ).closest( '.cherry-mega-menu li' ).length ) {
					$( '.mega-click-click-go' ).each( function() {
						$( this ).removeClass( 'mega-click-click-go' );
					});

					closePanels();
				}
			});

			$( 'li.cherry-mega-menu-has-children > a', menu ).on( {
				click: function( e ) {

					currentItem = $( this ).parent();

					if ( currentItem.hasClass( 'item-hide-mobile' ) && isTouchDevice() ) {
						return;
					}

					// Check for second click
					if ( currentItem.hasClass( 'mega-click-click-go' ) ) {
						currentItem.removeClass( 'mega-click-click-go' );
					} else {
						e.preventDefault();

						if ( currentItem.hasClass( 'mega-toggle-on' ) ) {
							hidePanel( $( this ), false );
						} else {
							currentItem.siblings().removeClass( 'mega-click-click-go' );

							if ( ! isMobile ) {
								showPanel( $( this ) );
							}
						}

						currentItem.addClass( 'mega-click-click-go' );
					}
				}
			});
		};

		openOnHover = function() {
			$( 'li.cherry-mega-menu-has-children', menu ).hoverIntent( {
				over: function() {

					// Check if is nested item in mega sub menu
					var inMega = isInMegamenu( $( this ) );

					if ( inMega ) {
						return;
					}

					if ( ! isMobile ) {
						showPanel( $( this ).children( 'a' ) );
					}
				},
				out: function() {

					// Check if is nested item in mega sub menu
					var inMega = isInMegamenu( $( this ) );

					if ( inMega ) {
						return;
					}

					if ( $( this ).hasClass( 'mega-toggle-on' ) ) {
						hidePanel( $( this ).children( 'a' ), false );
					}
				},
				timeout: 800
			});
		};

		init = function() {

			menu.removeClass( 'cherry-mega-no-js' );

			if ( isTouchDevice() || 'click' === menu.settings.event ) {
				openOnClick();
			} else {
				openOnHover();
			}

			$( window ).on( 'debouncedresize', function() {
				switchMobile();
			} ).trigger( 'debouncedresize' );
		};

		init();
	};

}( jQuery ) );

jQuery( document ).ready( function() {

	jQuery( '.cherry-mega-menu' ).each( function() {
		jQuery( this ).megaMenu();
	});
});
