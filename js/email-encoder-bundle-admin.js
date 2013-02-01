/*global jQuery*/
// Email Encoder Bundle Plugin - Admin
jQuery(function ($) {
	'use strict';

	var methodInfo = window.methodInfo;

	// remove message
	$('.settings-error')
		.hide()
		.fadeIn(600)
		.delay(3000)
		.fadeOut(600);

	// set info text for selected encoding method
	$('.method-info-select')
		.bind('change', function () {
			var method = $(this).val(),
				$desc = $(this).parent().find('span.description');

			if (methodInfo && methodInfo[method]) {
				$desc.hide();
				$desc.html(methodInfo[method].description || '');
				$desc.fadeIn();
			} else {
				$desc.html('');
			}
		})
		.change();

	// "has effect on"
	$('input#encode_emails')
		.change(function () {
			if ($(this).attr('checked')) {
				$('input#encode_mailtos').attr('checked', true);
			}
		})
		.change();

	$('input#encode_mailtos')
		.change(function () {
			if (!$(this).attr('checked')) {
				$('input#encode_emails').attr('checked', false);
			}
		});

	// rss feed
	$('input#filter_rss')
		.change(function () {
			$('input#protection_text').attr('disabled', !$(this).attr('checked'));
		})
		.change();

	// add form-table class to Encoder Form tables
	$('.email-encoder-form table').addClass('form-table');

	// slide postbox
	$('.postbox').find('.handlediv, .hndle').click(function () {
		var $inside = $(this).parent().find('.inside');

		if ($inside.css('display') === 'block') {
			$inside.css({ display:'block' }).slideUp();
		} else {
			$inside.css({ display:'none' }).slideDown();
		}
	});
});
