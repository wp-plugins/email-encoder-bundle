<?php
if ( ! function_exists( 'lim_email_ascii' ) ):

// Info (optional)
$lim_email_ascii = array(
	'name' => 'JavaScript ASCII',
	'description' => 'Uses javascript (<a href="http://rumkin.com/tools/mailto_encoder/" target="_blank">original source</a>).',
);

/**
 * lim_email_ascii()
 * Based on function from Tyler Akins (http://rumkin.com/tools/mailto_encoder/)
 *
 * @package Lim_Email_Encoder
 * @param string $email  the email to encode
 * @param string $display  the display showing on the page
 * @return string
 */
function lim_email_ascii( $email, $display ) {
    $MailLink = '<a href="mailto:' . $email . '">' . $display . '</a>';

    $MailLetters = '';

	for ($i = 0; $i < strlen($MailLink); $i ++)
	{
		$l = substr($MailLink, $i, 1);
		if (strpos($MailLetters, $l) === false)
		{
			$p = rand(0, strlen($MailLetters));
			$MailLetters = substr($MailLetters, 0, $p) .
			  $l . substr($MailLetters, $p, strlen($MailLetters));
		}
	}

	$MailLettersEnc = str_replace("\\", "\\\\", $MailLetters);
	$MailLettersEnc = str_replace("\"", "\\\"", $MailLettersEnc);

	$MailIndexes = '';
	for ($i = 0; $i < strlen($MailLink); $i ++)
	{
		$index = strpos($MailLetters, substr($MailLink, $i, 1));
		$index += 48;
		$MailIndexes .= chr($index);
	}
	$MailIndexes = str_replace("\\", "\\\\", $MailIndexes);
	$MailIndexes = str_replace("\"", "\\\"", $MailIndexes);

	return '<script language="javascript"><!--
ML="'. $MailLettersEnc .'";
MI="'. $MailIndexes .'";
OT="";
for(j=0;j<MI.length;j++){
OT+=ML.charAt(MI.charCodeAt(j)-48);
}document.write(OT);
// --></script>';
}

endif;
?>