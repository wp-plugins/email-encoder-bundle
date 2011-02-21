<?php
if ( ! function_exists( 'lim_email_escape' ) ):

// Info (optional)
$lim_email_escape = array(
	'name' => 'JavaScript Escape',
	'description' => 'Uses javascript eval() function (<a href="http://blueberryware.net/2008/09/14/email-spam-protection/" target="_blank">original source</a>).',
);

/**
 * lim_email_escape()
 * Taken from the plugin "Email Spam Protection" by Adam Hunter (http://blueberryware.net/2008/09/14/email-spam-protection/)
 *
 * @package Lim_Email_Encoder
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @return string
 */
function lim_email_escape( $email, $display ) {
	$string = 'document.write(\'<a href="mailto:' . $email . '">' . $display . '</a>\')';
	/* break string into array of characters, we can't use string_split because its php5 only :( */
	$split = preg_split('||', $string);
	$out =  '<script type="text/javascript">/*<![CDATA[*/ ' . "eval(unescape('";
	foreach ( $split as $c ) {
		/* preg split will return empty first and last characters, check for them and ignore */
		if ( !empty($c) ) {
			$out .= '%' . dechex(ord($c));
		}
	}
	$out .= "'))" . '/*]]>*/</script>';
	return $out;
}

endif;
?>