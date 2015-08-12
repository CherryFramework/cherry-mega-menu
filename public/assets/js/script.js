/*
 * debouncedresize: special jQuery event that happens once after a window resize
 *
 * latest version and complete README available on Github:
 * https://github.com/louisremi/jquery-smartresize
 *
 * Copyright 2012 @louis_remi
 * Licensed under the MIT license.
 */
(function($) {
	var $event = $.event,
		$special,
		resizeTimeout;
	$special = $event.special.debouncedresize = {
		setup: function() {
			$( this ).on( "resize", $special.handler );
		},
		teardown: function() {
			$( this ).off( "resize", $special.handler );
		},
		handler: function( event, execAsap ) {
			// Save the context
			var context = this,
				args = arguments,
				dispatch = function() {
					// set correct event type
					event.type = "debouncedresize";
					$event.dispatch.apply( context, args );
				};
			if ( resizeTimeout ) {
				clearTimeout( resizeTimeout );
			}
			execAsap ?
				dispatch() :
				resizeTimeout = setTimeout( dispatch, $special.threshold );
		},
		threshold: 150
	};
})(jQuery);

/*jslint browser: true, white: true */
/*global console,jQuery,megamenu,window,navigator*/

/**
 * Mega Menu jQuery Plugin
 */
(function ($) {
	"use strict";

	$.fn.megaMenu = function (options) {

		var menu = $(this),
			duration_timeout,
			trigger_fullscreen = 1200,
			trigger_desktop    = 970,
			trigger_tablet     = 768,
			is_mobile          = false,
			isTouchDevice, switchMobile, mobileOn, mobileOff, closePanels, hidePanel, showPanel, openOnClick, openOnHover, isInMegamenu, getMenuWidth, doubleTapToGo, init;

		menu.settings = $.extend({
			event:  menu.data('event'),
			effect: menu.data('effect'),
			mobile: menu.data('mobile-trigger'),
			parent: menu.data('parent-selector'),
			direction: menu.data('direction')
		}, options);

		isTouchDevice = function() {
			return ( 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0 );
		}

		isInMegamenu = function(el) {
			if ( el.parents('li.item-type-megamenu').length ) {
				return true;
			}
			return false;
		}

		switchMobile = function() {
			var width = window.innerWidth;
			if ( width <= menu.settings.mobile ) {
				mobileOn();
			} else {
				mobileOff();
			}
		}

		mobileOn = function() {
			menu.addClass('mega-menu-mobile-on').css('display', 'none').siblings('.cherry-mega-menu-mobile-trigger').addClass('mega-menu-mobile-on');
			menu.find('.cherry-mega-menu-sub').each(function(index, el) {
				$(this).css('display', 'none');
			});
			menu.find('.cherry-mega-menu-has-children').each(function(index, el) {
				var hide_this = $(this).data('hide-mobile');
				$(this).addClass(hide_this);
			});
			is_mobile = true;
		}

		mobileOff = function() {
			menu.removeClass('mega-menu-mobile-on').css('display', 'block').siblings('.cherry-mega-menu-mobile-trigger').removeClass('mega-menu-mobile-on');
			menu.find('.cherry-mega-menu-sub').each(function(index, el) {
				$(this).css('display', 'block');
			});
			menu.find('.cherry-mega-menu-has-children').each(function(index, el) {
				var hide_this = $(this).data('hide-mobile');
				$(this).removeClass(hide_this);
			});
			is_mobile = false;
		}

		getMenuWidth = function (string_width) {
			if (string_width.indexOf("%") >= 0) {
				var width = $(menu.settings.parent).width();
				width = ( width * parseInt(string_width) ) / 100;
				return width;
			}
			if (string_width.indexOf("px") >= 0) {
				var width = parseInt(string_width);
				return width;
			}
			return 0;
		}

		closePanels = function() {
			$('.mega-toggle-on > a', menu).each(function() {
				hidePanel($(this), true);
			});
		}

		hidePanel = function(anchor, immediate) {
			anchor.parent().removeClass('mega-toggle-on cherry-mega-menu-hover').triggerHandler("close_panel");
			anchor.siblings('.cherry-mega-menu-sub').removeClass('active-sub').addClass('in-transition');

			clearTimeout( duration_timeout );
			duration_timeout = setTimeout(
				function() {
					anchor.siblings('.cherry-mega-menu-sub').removeClass('in-transition');
				},
				cherry_mega_menu_data.duration
			);

			if ( is_mobile == true ) {
				anchor.siblings('.cherry-mega-menu-sub').slideUp('fast');
			}
		}

		showPanel = function(anchor) {

			if ( is_mobile ) {
				return;
			}

			// all open children of open siblings
			var item = anchor.parent();

			item
				.addClass('cherry-mega-menu-hover')
				.siblings()
				.find('.mega-toggle-on')
				.addBack('.mega-toggle-on')
				.children('a')
				.each(function() {
					hidePanel($(this), true);
				});

			var menu_width       = '100%',
				type             = item.data('sub-type'),
				width_fullscreen = item.data('width-fullscreen'),
				width_desktop    = item.data('width-desktop'),
				width_tablet     = item.data('width-tablet'),
				window_width     = $(document).width(),
				hr_position      = item.data('sub-hr-position'),
				vr_position      = item.data('sub-vr-position'),
				position         = hr_position,
				styles           = new Object(),
				duration         = cherry_mega_menu_data.duration;

			styles['-webkit-transition-duration'] = duration + 'ms';
			styles['-moz-transition-duration']    = duration + 'ms';
			styles['transition-duration']         = duration + 'ms';

			if ( menu.settings.direction == 'vertical' ) {
				position = vr_position;
			}

			if ( position != 'fullwidth' && window_width >= trigger_fullscreen ) {
				menu_width = width_fullscreen;
			} else if ( position != 'fullwidth' && trigger_desktop <= window_width && window_width < trigger_fullscreen ) {
				menu_width = width_desktop;
			} else if ( position != 'fullwidth' && window_width < trigger_desktop ) {
				menu_width = width_tablet;
			}

			switch (position) {
				case 'fullwidth':
					if ( type == 'standard' ) {
						menu_width = null;
						break
					}
					var menu_width = getMenuWidth(menu_width),
						indent = menu_width/2,
						left = $(menu.settings.parent).offset().left - menu.offset().left + parseInt( $(menu.settings.parent).css('padding-left') ) + parseInt( $(menu.settings.parent).css('border-left-width') )
					styles['left'] = left + 'px';
					break

				case 'center':
					if ( type == 'standard' ) {
						menu_width = null;
						break
					}
					var width  = getMenuWidth(menu_width),
						indent = width/2;
					styles['left']        = '50%';
					styles['margin-left'] = '-' + indent + 'px';
					break

				case 'left-container':
					if ( type == 'standard' ) {
						menu_width = null;
						break
					}
					styles['left']  = '0';
					styles['right'] = 'auto';
					break

				case 'right-container':
					if ( type == 'standard' ) {
						menu_width = null;
						break
					}
					styles['left']  = 'auto';
					styles['right'] = '0';
					break

				case 'left-parent':
					if ( type == 'standard' ) {
						menu_width = null;
						break
					}
					var left = item.position().left;
					styles['left']  = left + 'px';
					styles['right'] = 'auto';

					if ( menu_width == '100%' && left > 0 ) {
						menu_width      = 'auto';
						styles['right'] = '0';
					}

					break

				case 'right-parent':
					if ( type == 'standard' ) {
						menu_width = null;
						break
					}
					var right = menu.offset().left + parseInt( menu.css('border-left-width') ) + menu.width() - item.offset().left - item.width()
					styles['left']  = 'auto';
					styles['right'] = right + 'px';

					if ( menu_width == '100%' && right > 0 ) {
						menu_width     = 'auto';
						styles['left'] = '0';
					}

					break

				case 'vertical-full':

					var top = 0;

					if ( type == 'standard' ) {
						top = $(menu.settings.parent).offset().top - item.offset().top;
					}
					styles['top']   = top + 'px';
					styles['left']  = '100%';
					styles['right'] = 'auto';

					break

				case 'vertical-parent':
					var top = item.offset().top - menu.offset().top;
					if ( type == 'standard' ) {
						top = 0;
					}
					styles['top']   = top + 'px';
					styles['left']  = '100%';
					styles['right'] = 'auto';

					break
			}

			if ( menu_width != undefined && menu.settings.direction == 'vertical' && menu_width.indexOf("%") >= 0 ) {
				menu_width = 'auto';
			}

			if ( menu_width != null ) {
				styles['width'] = menu_width;
			}

			item.addClass('mega-toggle-on').triggerHandler("open_panel");

			anchor.siblings('.cherry-mega-menu-sub').css(styles).addClass('active-sub');
			if ( is_mobile == true ) {
				anchor.siblings('.cherry-mega-menu-sub').slideDown('fast');
			}

		}

		openOnClick = function() {
			// hide menu when clicked away from
			$(document).on('click touchstart', function(event) {
				if (!$(event.target).closest('.cherry-mega-menu li').length) {
					$('.mega-click-click-go').each(function(index, el) {
						$(this).removeClass('mega-click-click-go');
					});
					closePanels();
				}
			});

			$('li.cherry-mega-menu-has-children > a', menu).on({
				click: function (e) {
					if ( $(this).parent().hasClass('item-hide-mobile') ) {
						return;
					}

					// check for second click
					if ( $(this).parent().hasClass('mega-click-click-go') ) {
						$(this).parent().removeClass('mega-click-click-go');
					} else {
						e.preventDefault();

						if ( $(this).parent().hasClass('mega-toggle-on') ) {
							hidePanel($(this), false);
						} else {
							showPanel($(this));
						}
						$(this).parent().addClass('mega-click-click-go');
					}
				}
			});

		}

		openOnHover = function() {

			$('li.cherry-mega-menu-has-children', menu).hoverIntent({
				over: function () {

					// check if is nested item in mega sub menu
					var in_mega = isInMegamenu($(this));

					if ( in_mega ) {
						return;
					}

					showPanel($(this).children('a'));
				},
				out: function () {

					// check if is nested item in mega sub menu
					var in_mega = isInMegamenu($(this));

					if ( in_mega ) {
						return;
					}

					if ($(this).hasClass("mega-toggle-on")) {
						hidePanel($(this).children('a'), false);
					}
				},
				timeout: 800
			});
		}

		init = function() {

			menu.removeClass('cherry-mega-no-js');

			if ( isTouchDevice() || menu.settings.event === 'click' ) {
				openOnClick();
			} else {
				openOnHover();
			}

			$(window).on("debouncedresize", function( event ) {
				switchMobile();
			}).trigger("debouncedresize");

		}

		init();
	};

}(jQuery));

jQuery(document).ready(function(){
	"use strict";
	jQuery('.cherry-mega-menu').each(function() {
		jQuery(this).megaMenu();
	});
});