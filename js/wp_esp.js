(function( $ ){

$( document ).ready(function(){

	$( '#ajax_encode' ).click(function(){
		var gets = {
				ajax: true,
				email: $( '#email' ).val(),
				display: $( '#display' ).val() || $( '#email' ).val(),
				method: $( '#encode_method' ).val()
			};

		// get the encoded email link
		$.get( '', gets, function(data){
			$( '#encoded_output' ).val( data );
		});

		// show example how it will appear on the page
		$( '#example' ).html( '<a href="mailto:'+ gets.email +'">'+ gets.display +'</a>' );
	});

});

})( jQuery );
