<?php
if ( ! function_exists( 'anti_email_spam' ) ):

/**
 * Email encode method uses javascript to split the email in 2 parts and set into seperate vars
 * Taken from the plugin "Anti-email Spam"
 * Credits goes to John Godley
 * @link http://urbangiraffe.com/plugins/anti-email-spam/
 * @package Lim_Email_Spam_Protect
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @param string $encode_display  also encode the display
 * @return string
 */
function anti_email_spam( $email, $display, $encode_display ) {
	if ( $encode_display )
		$display = Lim_Email_Encoder::get_htmlent( $display );

	$parts = explode ('@', substr ($email, 1));
	$str = $matches[1].'<script type="text/javascript">/*<![CDATA[*/';
	$str .= 'var username = "'.$parts[0].'"; var hostname = "'.$parts[1].'";';
	$str .= 'document.write("<a href=" + "mail" + "to:" + username + ';
	$str .= '"@" + hostname + ">'.$display.'<\/a>")';
	$str .= '/*]]>*/</script>';
	return $str;
}

endif;
?>