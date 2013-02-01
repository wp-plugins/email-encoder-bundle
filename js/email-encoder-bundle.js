/*global jQuery*/
// Email Encoder Bundle Plugin
jQuery(function($){
	'use strict';

	var $wrap = $('div.email-encoder-form'),
		$email = $wrap.find('#email'),
		$display = $wrap.find('#display'),
		prevEmail;

	// get encoded email ( ajax call )
	var getEncoded = function () {
		// stop when email field is empty
		if (!$email.val()) {
			return;
		}

		// empty the output field
		$wrap.find('#encoded_output').val('');

		// get the encoded email link
		$.get('', {
			ajaxEncodeEmail: true,
			email: $email.val(),
			display: $display.val() || $email.val(),
			method: $wrap.find('#encode_method').val()
		}, function (data) {
			$wrap.find('#encoded_output').val(data);
			$wrap.find('.output').fadeIn();
		});
	};

	// hide output
	$wrap.find('.nodis').hide();

	// auto-set display field
	$email.keyup(function(){
		var email = $email.val(),
			display = $display.val();

		if (!display || display === prevEmail) {
			$display.val(email);
		}

		prevEmail = email;
	});

	// get encoded link on these events
	$wrap.find('#email, #display')
			.keyup(function () {
				if ($display.val().length > 0) {
					// show example how it will appear on the page
					$wrap.find('#example')
						.html('<a href="mailto:' + $email.val() + '">' + $display.val() + '</a>')
						.parents('tr').fadeIn();
				} else {
					$wrap.find('#example').parents('tr').fadeOut();
				}

				// clear code field
				$wrap.find('.output').fadeOut();
				$wrap.find('#encoded_output').val('');
			})
			.keyup();

	$wrap.find('#encode_method').bind('change keyup', function () {
		getEncoded();
	});

	$wrap.find('#ajax_encode').click(function(){
		getEncoded();
	});

});
