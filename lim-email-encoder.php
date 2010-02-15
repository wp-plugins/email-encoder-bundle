<?php
/**
 * Class Lim_Email_Encoder
 * Protecting email-spamming by replacing them with one of the registered encoding- or javascript-methods
 * @author   Victor Villaverde Laan
 * @package  Lim_Email_Encoder
 * @version  0.1
 * @link     http://www.freelancephp.net/email-encoder
 * @license  Dual licensed under the MIT and GPL licenses
 */
class Lim_Email_Encoder {

	/**
	 * @var array
	 */
	var $methods = array();

	/**
	 * @var array
	 */
	var $options = array(
			'method' => 'default_encode',
			'encode_display' => TRUE, // encode display with the default encoder
			'encode_mailto' => TRUE,
			'replace_emails' => TRUE,
		);

	/**
	 * PHP4 constructor
	 */
	function Lim_Email_Encoder() {
		$this->__construct();
	}

	/**
	 * PHP5 constructor
	 */
	function __construct() {
		// include all available method files
		$this->_include_method_files();
	}

	/**
	 * Set the encode method to use
	 * @param string $key  can be a method key or 'random'
	 */
	function set_method( $key ) {
		if ( 'random' == $key ) {
			// set a random method
			$this->options['method'] = array_rand( $this->methods );
		} else if ( ! key_exists( $key, $this->methods ) ) {
			// set default method
			$this->options['method'] = 'default_encode';
		} else {
			$this->options['method'] = $key;
		}
	}

	/**
	 * Encode the given email into an encoded link
	 * @param string $email
	 * @param string $display
	 * @return string
	 */
	function encode_email( $email, $display = NULL ) {
		if ( $display === NULL )
			$display = $email;

		// get the encode method to use
		$encode_method = $this->methods[ $this->options['method'] ];

		// get encoded email code
		return call_user_func( $encode_method, $email, $display, $this->options['encode_display'] );
	}

	/**
	 * Filter for encoding emails in the given content
	 * @param string $content
	 * @return string
	 */
	function encode_filter( $content ) {
		// replace plain emails to a content tag
		if ( $this->options['replace_emails'] ) {
			$email_pattern = '/([ ])([A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6})/i';
			$replacement = '${1}[encode_email email="${2}" display="${2}"]';
			$content = preg_replace( $email_pattern, $replacement, $content );
		}

		// encode mailto links
		if ( $this->options['encode_mailto'] ) {
			$mailto_pattern = '/<a.*?href=["\']mailto:(.*?)["\'].*?>(.*?)<\/a>/i';
			$content = preg_replace_callback( $mailto_pattern, array( $this, '_callback' ), $content );
		}

		// replace content tags [encode_email email="?" display="?"] to mailto links
		// this code is partly taken from the plugin "Fay Emails Encoder"
		// Credits goes to Faycal Tirich (http://faycaltirich.blogspot.com)
		$tag_pattern = '/\[encode_email\s+email=["\'](.*?)["\']\s+display=["\'](.*?)["\']]/i';
		$content = preg_replace_callback( $tag_pattern, array( $this, '_callback' ), $content );

		return $content;
	}

	/**
	 * Convert randomly chars to htmlentities
	 * This method is partly taken from WordPress
	 * @link http://codex.wordpress.org/Function_Reference/antispambot
	 * @static
	 * @param string $value
	 * @return string
	 */
	function get_htmlent( $value ) {
		$enc_value = '';
		srand( (float) microtime() * 1000000 );

		for ( $i = 0; $i < strlen( $value ); $i = $i + 1 ) {
			$j = floor( rand( 0, 1 ) );

			if ( $j == 0 ) {
				$enc_value .= '&#' . ord( substr( $value, $i, 1 ) ).';';
			} elseif ( $j == 1 ) {
				$enc_value .= substr( $value, $i, 1 );
			}
		}

		$enc_value = str_replace( '@', '&#64;', $enc_value );

		return $enc_value;
	}

	/**
	 * Callback for encoding email
	 * @param array $match
	 * @return string
	 */
	function _callback( $match ) {
		return $this->encode_email( $match[1], $match[2] );
	}

	/**
	 * Including all method files
	 * @return void
	 */
	function _include_method_files() {
		$method_dir = dirname(__FILE__) . '/methods';
		$handle = opendir( $method_dir );

		// dir not found
		if ( ! $handle )
			return;

		// include all methods inside the method folder
		while ( false !== ($file = readdir($handle)) ) {
			if ( '.php' == substr( $file, -4 ) ) {
				require_once $method_dir . '/' . $file;

				$fn = substr( $file, 0, -4 );

				if ( function_exists( $fn ) )
					$this->methods[$fn] = $fn;
			}
		}

		closedir( $handle );
	}

} // end class Lim_Email_Encoder

?>