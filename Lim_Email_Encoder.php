<?php
/**
 * Lim_Email_Encoder Class
 *
 * Protecting email-spamming by replacing them with one of the registered encoding- or javascript-methods
 *
 * @package  Lim_Email_Encoder
 * @author   Victor Villaverde Laan
 * @version  0.2.1
 * @link     http://www.freelancephp.net/email-encoder-php-class/
 * @license  MIT license
 */
class Lim_Email_Encoder {

	/**
	 * @var array
	 */
	var $methods = array();

	/**
	 * @var string
	 */
	var $method = NULL;


	/**
	 * PHP4 constructor
	 */
	function Lim_Email_Encoder() {
		$this->__construct();
	}

	/**
	 * PHP5 constructor
	 */
	function __construct( $method = NULL ) {
		// include all available method files
		$this->_load_methods();

		// set method
		$this->set_method( $method );
	}

	/**
	 * Set the encode method to use
	 * @param string $method  can be the name of the method or 'random'
	 * @return $this
	 */
	function set_method( $method ) {
		if ( 'random' == $method ) {
			// set a random method
			$this->method = array_rand( $this->methods );
		} elseif ( ! key_exists( $method, $this->methods ) ) {
			// set default method
			$this->method = 'lim_email_html_encode';
		} else {
			// add 'lim_email_' prefix if not already set
			$this->method = ( strpos( $method, 'lim_email_' ) !== FALSE ) ? $method : 'lim_email_' . $method;
		}

		return $this;
	}

	/**
	 * Encode the given email into an encoded HTML link
	 * @param string $email
	 * @param string $display Optional, if not set display will be the email
	 * @return string
	 */
	function encode( $email, $display = NULL ) {
		if ( $display === NULL )
			$display = $email;

		// get encoded email code
		return call_user_func( $this->method, $email, $display );
	}

	/**
	 * Encode all emails of the given content
	 * @param string $content
	 * @param boolean $enc_tags Optional, default TRUE
	 * @param boolean $enc_plain_emails Optional, default TRUE
	 * @param boolean $enc_mailtos  Optional, default TRUE
	 * @return string
	 */
	function filter( $content, $enc_tags = TRUE, $enc_plain_emails = TRUE, $enc_mailtos = TRUE ) {
		// encode mailto links
		if ( $enc_mailtos ) {
			$mailto_pattern = '/<a.*?href=["\']mailto:(.*?)["\'].*?>(.*?)<\/a>/i';
			$content = preg_replace_callback( $mailto_pattern, array( $this, '_callback' ), $content );
		}

		// replace content tags [encode_email email="?" display="?"] to mailto links
		// this code is partly taken from the plugin "Fay Emails Encoder"
		// Credits goes to Faycal Tirich (http://faycaltirich.blogspot.com)
		if ( $enc_tags ) {
			$tag_pattern = '/\[encode_email\s+email=["\'](.*?)["\']\s+display=["\'](.*?)["\']]/i';
			$content = preg_replace_callback( $tag_pattern, array( $this, '_callback' ), $content );
		}

		// replace plain emails
		if ( $enc_plain_emails ) {
			$email_pattern = '/([A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6})/i';
			$content = preg_replace_callback( $email_pattern, array( $this, '_callback' ), $content );
		}

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
		// check if antispambot WordPress function exists
		if ( ! function_exists( 'antispambot' ) )
			return antispambot( $value );

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
		if ( count( $match ) == 2 )
			return $this->encode( $match[1] );

		return $this->encode( $match[1], $match[2] );
	}

	/**
	 * Load available methods
	 * @return void
	 */
	function _load_methods() {
		$method_dir = dirname(__FILE__) . '/methods';
		$handle = opendir( $method_dir );

		// dir not found
		if ( ! $handle )
			return;

		// include all methods inside the method folder
		while ( false !== ($file = readdir($handle)) ) {
			if ( '.php' == substr( $file, -4 ) ) {
				require_once $method_dir . '/' . $file;

				$name = substr( $file, 0, -4 );
				$fn = 'lim_email_' . $name;

				if ( function_exists( $fn ) ) {
					// set method with info
					$this->methods[$fn] = ( isset( ${ $fn } ) ) 
										? ${ $fn }
										: array( 'name' => $name, 'description' => $name );
				}
			}
		}

		closedir( $handle );
	}

} // end class Lim_Email_Encoder

/*?> // ommit closing tag, to prevent unwanted whitespace at the end of the parts generated by the included files */