(function( $ ){

$(function(){

	/**
	 * Encoded Form
	 */
	var prevEmail = $( '#email' ).val(),
		prevDisplay = $( '#display' ).val(),
		prevMethod = $( '#encode_method' ).val(),
		getEncoded = function ( forceCall ) {
			var email = $( '#email' ).val(),
				display = $( '#display' ).val(),
				method = $( '#encode_method' ).val();

			// stop when email field is empty
			if ( email == prevEmail && display == prevDisplay && ( ! email || method == prevMethod ) && ! forceCall )
				return;

			// empty output
			$( '#example' ).empty();
			$( '#encoded_output' ).val( '' );

			// get the encoded email link
			$.get( '', {
					ajax: true,
					email: email,
					display: display || email,
					method: method
				},
				function(data){
					$( '#encoded_output' ).val( data );

					// show example how it will appear on the page
					$( '#example' ).html( '<a href="mailto:'+ email +'">'+ display +'</a>' );

					// set prev values
					prevEmail = email;
					prevDisplay = display;
					prevMethod = method;
			});
		};

	// get encoded link on these events
	$( '#email, #display' ).blur(function(){
		getEncoded();
	});
	$( '#encode_method' ).bind( 'change blur keyup', function(){
			getEncoded();
		})
		.blur();
	$( '#ajax_encode' ).click(function(){
		getEncoded( true );
	});

	// set info text for selected encoding method
	$( '.method-info-select' ).bind( 'change blur keyup', function(){
			var method = $( this ).val(),
				$desc = $( this ).parent().find( 'span.description' );

			if ( methodInfo && methodInfo[ method ] ) {
				$desc.html( methodInfo[ method ][ 'description' ] || '' );
			} else {
				$desc.html( '' );
			}
		})
		.blur();

	/**
	 * Admin Panel
	 */
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

});

})( jQuery );
