<?php
if ( ! function_exists( 'lim_email_html_encode' ) ):

// Info (optional)
$lim_email_html_encode = array(
	'name' => 'Html Encode',
	'description' => 'Email encode method using antispambot() built-in WordPress (<a href="http://codex.wordpress.org/Function_Reference/antispambot" target="_blank">more info</a>).',
);

/**
 * lim_email_html_encode()
 * Default email encode method converting email to html entities
 * 
 * @package Lim_Email_Encoder
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @return string
 */
function lim_email_html_encode( $email, $display ) {
	$email = Lim_Email_Encoder::get_htmlent( $email );
	$display = Lim_Email_Encoder::get_htmlent( $display );

	// return encode mailto link
	return '<a href="mailto:' . $email . '">' . $display . '</a>';
}

endif;

?>