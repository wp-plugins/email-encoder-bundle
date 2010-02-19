<?php
// WordPress only !!!
if ( ! function_exists( 'wp_antispambot' ) AND function_exists( 'antispambot' ) ):

/**
 * Email encode method using antispambot() built-in WordPress
 * @link http://codex.wordpress.org/Function_Reference/antispambot
 * @package Lim_Email_Encoder
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @param string $encode_display  also encode the display
 * @return string
 */
function wp_antispambot( $email, $display, $encode_display ) {
	if ( $encode_display )
		$display = antispambot( $display );

	// return encode mailto link
	return '<a href="mailto:' . antispambot( $email ) . '">' . $display . '</a>';
}

endif;

?>