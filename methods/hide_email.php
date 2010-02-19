<?php
if ( ! function_exists( 'hide_email' ) ):

/**
 * hide_email is an email encode method
 * Credits goes to Maurits van der Schee
 * @link http://www.maurits.vdschee.nl/php_hide_email/
 * @package Lim_Email_Encoder
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @param string $encode_display  also encode the display
 * @return string
 */
function hide_email( $email, $display, $encode_display ) {
	if ( $encode_display )
		$display = Lim_Email_Encoder::get_htmlent( $display );

	$character_set  = '+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
	$key = str_shuffle($character_set);
	$cipher_text = '';
	$id = 'e'.rand(1,999999999);
	for ($i=0;$i<strlen($email);$i+=1)
		$cipher_text.= $key[strpos($character_set,$email[$i])];
	$script = 'var a="'.$key.'";var b=a.split("").sort().join("");var c="'.$cipher_text.'";var d="";';
	$script.= 'for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));';
	$script.= 'document.getElementById("'.$id.'").innerHTML="<a href=\\"mailto:"+d+"\\">'.$display.'</a>"';
	$script = "eval(\"".str_replace(array("\\",'"'),array("\\\\",'\"'), $script)."\")";
	$script = '<script type="text/javascript">/*<![CDATA[*/'.$script.'/*]]>*/</script>';
	return '<span id="'.$id.'">'.$display.'</span>'.$script;
}

endif;
?>