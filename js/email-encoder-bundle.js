(function( $ ){

$(function(){

	/**
	 * Encoded Form
	 */
	(function(){
		var prevEmail, getEncoded,
			$wrap = $( 'div.email-encoder-form' ),
			$email = $wrap.find( '#email' ),
			$display = $wrap.find( '#display' );

		// hide output
		$wrap.find( '.nodis' ).hide();

		// auto-set display field
		$email.keyup(function(){
			var email = $email.val(),
				display = $display.val();

			if ( ! display || display == prevEmail )
				$display.val( email );

			prevEmail = email;
		});

		// get encoded email ( ajax call )
		getEncoded = function () {
			// stop when email field is empty
			if ( ! $email.val() )
				return;

			// empty output
			$wrap.find( '#encoded_output' ).val( '' );

			// get the encoded email link
			$.get( '', {
					ajax: true,
					email: $email.val(),
					display: $display.val() || $email.val(),
					method: $wrap.find( '#encode_method' ).val()
				},
				function(data){
					$wrap.find( '#encoded_output' ).val( data );
					$wrap.find( '.output' ).slideDown();
			});
		};

		// get encoded link on these events
		$wrap.find( '#email, #display' ).keyup(function(){
			// show example how it will appear on the page
			$wrap.find( '#example' ).html( '<a href="mailto:'+ $email.val() +'">'+ $display.val() +'</a>' );

			// clear code field
			$wrap.find( '.output' ).slideUp();
			$wrap.find( '#encoded_output' ).val( '' );
		})
		.keyup();

		$wrap.find( '#encode_method' ).bind( 'change keyup', function(){
			getEncoded();
		});

		$wrap.find( '#ajax_encode' ).click(function(){
			getEncoded();
		});

		// set info text for selected encoding method
		$wrap.find( '.method-info-select' ).bind( 'change blur keyup', function(){
				var method = $( this ).val(),
					$desc = $( this ).parent().find( 'span.description' );

				if ( methodInfo && methodInfo[ method ] ) {
					$desc.html( methodInfo[ method ][ 'description' ] || '' );
				} else {
					$desc.html( '' );
				}
			})
			.blur();
	}());

	/**
	 * Admin Panel
	 */
	(function(){
		// skip rest when not admin
		if ( $( '#adminmenu' ).size() == 0 )
			return;

		// prevent toggle when dragging
		var toggle = true;

		// set sortable boxes
		$( '.meta-box-sortables' ).sortable({
			items: '.postbox',
			handle: 'h3',
			placeholder: 'sortable-placeholder',
			forcePlaceholderSize: true,
			stop: function () {
				toggle = false;
			}
		});

		// set box content toggle
		$( 'h3.hndle, div.handlediv' ).click(function(){
			if( toggle )
				$( this ).parent().find( '.inside' ).toggle();

			toggle = true;
		});

		// set margins
		$( 'div.postbox div.inside' )
			.css({ 'margin-left': '10px', 'margin-right': '10px' });

		// add form-table class to Encoder Form tables
		$( '.email-encoder-form table' ).addClass( 'form-table' );
	}());

});

})( jQuery );
