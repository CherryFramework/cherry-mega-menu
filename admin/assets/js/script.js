/**
 * Cherry mega menu custom scripts
 *
 * Mega Menu jQuery Plugin.
 * based on Mega Menu plugin - http://www.maxmegamenu.com/
 */
(function ($) {
	"use strict";

	$.fn.megaMenu = function (options) {

		var panel         = $("<div />");

		panel.settings = $.extend({
			cols:            6,
			menu_item_id:    0,
			menu_item_title: '',
			menu_item_depth: 0
		}, options);

		var popups_wrap     = $('.popup-wrapper_');
		var current_popup   = 'id-' + panel.settings.menu_item_id;
		var widget_selector = false;

		if ( $( '#' + current_popup ).length == 0 ) {
			var _ui_class = popups_wrap.data('ui-class');
			popups_wrap.append('<div id="' + current_popup + '" class="white-popup-block ' + _ui_class + '"><div class="popup-content_"><div class="popup-loading_"></div></div></div>');
		}

		panel.log = function (message) {
			if (window.console && console.log) {
				console.log(message);
			}

			if (message == -1) {
				alert(cherry_mega_menu.nonce_check_failed);
			}
		};


		panel.init = function () {

			panel.log(cherry_mega_menu.debug_launched + " " + panel.settings.menu_item_id);

			$.magnificPopup.open({
				items: {
					src: '#' + current_popup
				},
				type: 'inline',
				callbacks: {
					open: function() {
						popup_request();
					}
				}
			});

			$(document).on('click', '.cherry-mega-menu-save-settings', function(event) {
				event.preventDefault();
				save_settings( $(this) );
			});
			$(document).on('change', '.toggle_menu', function(event) {
				event.preventDefault();
				save_settings( $(this) );
			});

		};

		var save_settings = function( element ) {

			var form = element.parents('form'),
				data = form.serialize();

			start_saving();

			$.post(ajaxurl, data, function (submit_response) {
				end_saving();
				panel.log(submit_response);
			});

		}

		var popup_request = function() {

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: "cherry_mega_menu_get_popup",
					_wpnonce: cherry_mega_menu.nonce,
					menu_item_id: panel.settings.menu_item_id,
					menu_item_title: panel.settings.menu_item_title,
					menu_item_depth: panel.settings.menu_item_depth,
					total_cols: panel.settings.cols
				},
				cache: false,
				beforeSend: function() {
					$('#' + current_popup + ' .popup-content_').html('<div class="popup-loading_"></div>');
				},
				complete: function() {
					 $('#' + current_popup + ' .popup-content_').find('.popup-loading_').remove();
				},
				success: function(response) {

					var content = response.content;
					// init tabs
					$('#' + current_popup + ' .popup-content_').addClass('depth-' + panel.settings.menu_item_depth).html(content).on('click.cherry_menu_tabs', '.vertical-tabs_.vertical-tabs_width_small_ a', function(event) {
						event.preventDefault();
						var parent      = $(this).parents('.popup-content_'),
							tab_content = parent.find('.mega-menu-tabs-content_');

						$(this).parent().addClass('active').siblings().removeClass('active');
						tab_content.find( '#' + $(this).data('tab') ).css('visibility', 'visible').siblings().css('visibility', 'hidden');

						init_settings_scripts();
					})
					$('#' + current_popup + ' .popup-content_ .vertical-tabs_.vertical-tabs_width_small_active a:first').trigger('click.cherry_menu_tabs');

					tabs_height();
					init_settings_scripts();

					// init
					if ( $('#' + current_popup + ' #cherry-menu-tab-mega_menu').length ) {
						select_widgets();
						sort_widgets();
					}
				}
			});

		}

		var tabs_height = function() {
			var height  = 0;

			$('#' + current_popup + ' .mega-menu-tabs-content-item_').each(function(index, el) {
				if ( parseInt( $(this).outerHeight() ) > height ) {
					height = parseInt( $(this).outerHeight() );
				}
			});

			$('#' + current_popup + ' .mega-menu-tabs-content_').height(height);
		}

		var init_settings_scripts = function() {
			$('input[data-init-script="icon-picker"]').each(function(index, el) {
				$(this).iconpicker();
			});
		}
		var start_saving = function() {
			$('.popup-saving_').show();
		}

		var end_saving = function() {

			$('.popup-saving_').hide();

			var timeout = false;

			if (!timeout) {
				$('.popup-saved_').stop().fadeIn('fast');
				timeout = setTimeout( function() {
					$('.popup-saved_').stop().fadeOut('fast');
				}, 1000 );
			}

		}

		var sort_widgets = function() {

			var widget_area = $('#' + current_popup).find('#widgets'),
				_area_width = widget_area.innerWidth(),
				_grid_size  = parseInt( _area_width/panel.settings.cols );

			widget_area.sortable({
				forcePlaceholderSize: true,
				placeholder: "drop-area",
				start: function (event, ui) {
					$(".widget").removeClass("open");
					ui.item.data('start_pos', ui.item.index());
				},
				stop: function (event, ui) {
					// clean up
					ui.item.removeAttr('style');

					var start_pos = ui.item.data('start_pos');

					if (start_pos !== ui.item.index()) {
						ui.item.trigger("on_drop");
					}
				}
			})

			$('.widget', widget_area).each(function() {
				add_events_to_widget($(this));
			});
		}

		var select_widgets = function( widget_selector ) {

			var widget_selector = $('#' + current_popup).find('#widget_selector');

			widget_selector.on('mouseup', function() {

				var selector = $(this);

				if (selector.val() != 'disabled' && selector.val() != undefined) {

					start_saving();

					var postdata = {
						action: "cherry_mega_menu_add_widget",
						id_base: selector.val(),
						menu_item_id: panel.settings.menu_item_id,
						total_cols: panel.settings.cols,
						title: selector.find('option:selected').text(),
						_wpnonce: cherry_mega_menu.nonce
					};

					$.post(ajaxurl, postdata, function (response) {

						end_saving();

						$(".no_widgets").hide();

						$("#widgets").append(response.content);
						tabs_height();

						if ( response.type = 'success' ) {
							add_events_to_widget( $('#' + current_popup + ' #' + response.id ) );
						}

					});

				}


			});
		}

		var add_events_to_widget = function (widget) {

			var widget_spinner  = widget.find(".spinner"),
				resize          = widget.find(".widget-resize"),
				edit            = widget.find(".widget-edit"),
				widget_inner    = widget.find(".widget-inner"),
				widget_id       = widget.attr("data-widget-id");

			widget.bind("on_drop", function () {

				start_saving();

				var position = $(this).index();

				$.post(ajaxurl, {
					action: "cherry_mega_menu_move_widget",
					widget_id: widget_id,
					position: position,
					menu_item_id: panel.settings.menu_item_id,
					_wpnonce: cherry_mega_menu.nonce
				}, function (move_response) {
					end_saving();
					panel.log(move_response);
				});
			});

			resize.on("click", function () {

				var cols = parseInt(widget.attr("data-columns"), 10),
					_action = $(this).data('action');

				if ( _action == 'expand' && cols < panel.settings.cols ) {
					cols = cols + 1;
					widget.attr("data-columns", cols);
				}

				if ( _action == 'contract' && cols > 0 ) {
					cols = cols - 1;
					widget.attr("data-columns", cols);
				}

				widget.find('.widget-cols-counter b').html(cols);

				start_saving();

				$.post(ajaxurl, {
					action: "cherry_mega_menu_update_columns",
					widget_id: widget_id,
					columns: cols,
					_wpnonce: cherry_mega_menu.nonce
				}, function (response) {
					end_saving();
					panel.log(response);
				});

			});


			edit.on("click", function () {

				if (! widget.hasClass("open") && ! widget.data("loaded")) {

					widget_spinner.show();

					// retrieve the widget settings form
					$.post(ajaxurl, {
						action: "cherry_mega_menu_edit_widget",
						widget_id: widget_id,
						_wpnonce: cherry_mega_menu.nonce
					}, function (form) {

						var $form = $(form);

						// bind delete button action
						$(".delete", $form).on("click", function (e) {
							e.preventDefault();

							var data = {
								action: "cherry_mega_menu_delete_widget",
								widget_id: widget_id,
								_wpnonce: cherry_mega_menu.nonce
							};

							$.post(ajaxurl, data, function (delete_response) {
								widget.remove();
								panel.log(delete_response);
							});

						});

						// bind close button action
						$(".close", $form).on("click", function (e) {
							e.preventDefault();

							widget.toggleClass("open");
						});

						// bind save button action
						$form.on("submit", function (e) {
							e.preventDefault();

							var data = $(this).serialize();

							start_saving();

							$.post(ajaxurl, data, function (submit_response) {
								end_saving();
								panel.log(submit_response);
							});

						});

						widget_inner.html($form);

						widget.data("loaded", true).toggleClass("open");

						widget_spinner.hide();
					});

				} else {
					widget.toggleClass("open");
				}

				// close all other widgets
				$(".widget").not(widget).removeClass("open");

			});

			return widget;
		};

		panel.init();

	};

}(jQuery));


jQuery(function ($) {

	$( '#menu-management' ).on( 'hover, touchEnd, MSPointerUp, pointerup' , '.menu-item:not(.cherry-mega-menu-processed)' , function(e){

		var menu_item  = $(this),
			title      = menu_item.find('.menu-item-title').html(),
			id         = parseInt(menu_item.attr('id').match(/[0-9]+/)[0], 10),
			launch_btn = $("<span>").addClass("cherry-mega-menu-launch").attr({
				'data-id': id,
				'data-title': title
			});

		menu_item.addClass('cherry-mega-menu-processed');

		$('.item-title', menu_item).append(launch_btn);

		launch_btn.html( cherry_mega_menu.launch_popup ).on('click', function(e) {
			e.preventDefault();
			var depth = menu_item.attr('class').match(/\menu-item-depth-(\d+)\b/)[1];
			$(this).megaMenu({
				cols:            cherry_mega_menu.cols,
				menu_item_id:    id,
				menu_item_title: title,
				menu_item_depth: depth
			});
		});
	});

});