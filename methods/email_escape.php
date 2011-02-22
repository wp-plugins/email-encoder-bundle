<?php
if ( ! function_exists( 'email_escape' ) ):

/**
 * Email encode method uses javascript eval() and unescape()
 * Taken from the plugin "Email Spam Protection" (v1.2)
 * Credits goes to Adam Hunter
 * @link http://blueberryware.net/2008/09/14/email-spam-protection/
 * @package Lim_Email_Encoder
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @param string $encode_display  also encode the display
 * @return string
 */
function email_escape( $email, $display, $encode_display ) {
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