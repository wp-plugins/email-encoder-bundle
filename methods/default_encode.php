<?php
if ( ! function_exists( 'default_encode' ) ):

/**
 * Default email encode method converting email to html entities
 * @package Lim_Email_Encoder
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @param string $encode_display  also encode the display
 * @return string
 */
function default_encode( $email, $display, $encode_display ) {
	$email = Lim_Email_Encoder::get_htmlent( $email );

	if ( $encode_display )
		$display = Lim_Email_Encoder::get_htmlent( $display );

	// return encode mailto link
	return '<a href="mailto:' . $email . '">' . $display . '</a>';
}

endif;

?>