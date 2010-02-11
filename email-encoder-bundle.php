<?php
/*
Plugin Name: Email Encoder Bundle
Plugin URI: http://www.freelancephp.net/email-encoder
Description: Protecting email-spamming by replacing them with one of the registered encoding-methods
Author: Victor Villaverde Laan
Version: 0.1
Author URI: http://www.freelancephp.net
*/
// include parent class
require_once dirname(__FILE__) . '/lim-email-encoder.php';

/**
 * Class WP_Email_Encoder, child of Lim_Email_Encoder
 * @package Lim_Email_Encoder
 * @category WordPress Plugins
 */
class WP_Email_Encoder extends Lim_Email_Encoder {

	/**
	 * Prefix for options entry and being used as text domain (for translations)
	 * @var string
	 */
	var $prefix = 'wp_esp';

	/**
	 * @var array
	 */
	var $wp_options = array(
			'filter_comments' => TRUE,
			'form_on_site' => FALSE, // set encoder form on the website
			'powered_by' => TRUE,
		);

	/**
	 * PHP4 constructor
	 */
	function WP_Email_Encoder() {
		$this->__construct();
	}

	/**
	 * PHP5 constructor
	 */
	function __construct() {
		parent::__construct();

		// add all $wp_options to $options
		$this->options = array_merge( $this->options, $this->wp_options );
		// set option values
		$this->_set_options();

		// add filters
		add_filter( 'the_content', array( &$this, 'encode_filter' ), 100 );

		// also filter comments
		if ( $this->options['filter_comments'] )
			add_filter( 'comment_text', array( &$this, 'encode_filter' ), 100 );

		// add actions
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		if ( $this->options['form_on_site'] )
			add_action( 'the_posts', array( &$this, 'the_posts' ) );

		// set uninstall hook
		if ( function_exists( 'register_deactivation_hook' ) )
			register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ));
	}

	/**
	 * Callback for the_post action
	 * @param array $posts
	 */
	function the_posts( $posts ) {
		if ( empty( $posts ) )
			return $posts;

		foreach ( $posts as $key => $post ) {
			if ( stripos( $post->post_content, '[email_encoder_form]' ) > -1 ) {
				// add style and script for ajax encoder
				wp_enqueue_script( 'email_encoder', plugins_url( 'js/wp_esp.js', __FILE__ ), array( 'jquery' ) );
				// replace tag by form
				$posts[$key]->post_content = str_replace( '[email_encoder_form]', $this->get_encoder_form(), $post->post_content );
				break;
			}
		}

		return $posts;
	}

	/**
	 * Callback admin_menu
	 */
	function admin_menu() {
		if ( function_exists('add_options_page') AND current_user_can('manage_options') ) {
			// add options page
			$page = add_options_page( 'Email Encoder Bundle', 'Email Encoder Bundle',
								'manage_options', __FILE__, array( &$this, 'options_page' ) );

			// add scripts
			add_action( 'admin_print_scripts-' . $page, array( &$this, 'admin_print_scripts' ) );
		}
	}

	/**
	 * Callback admin_init
	 */
	function admin_init() {
		// register settings
		register_setting( $this->prefix, $this->prefix . 'options' );
	}

	/**
	 * Callback admin_print_scripts
	 */
	function admin_print_scripts() {
		// add script for ajax encoder
		wp_enqueue_script( 'email_encoder', plugins_url( 'js/wp_esp.js', __FILE__ ), array( 'jquery' ) );
	}

	/**
	 * Admin options page
	 */
	function options_page() {
?>
	<div class="wrap">
		<h2>Email Encoder Bundle</h2>

		<div>
			<h3>Settings</h3>
			<form method="post" action="options.php">
			<?php 
				settings_fields( $this->prefix );
				$this->_set_options();
				$options = $this->options;
			?>
				<fieldset class="options">
					<table class="form-table">
					<tr>
						<th><label for="<?php echo $this->prefix ?>options[method]"><?php _e( 'Encoding method', $this->prefix ) ?></label></th>
						<td><select id="<?php echo $this->prefix ?>options[method]" name="<?php echo $this->prefix ?>options[method]" class="postform">
						<?php foreach ( $this->methods AS $key => $method ): ?>
							<option value="<?php echo $key ?>" <?php if ( $options['method'] == $key ) echo 'selected="selected"' ?>><?php _e( $key, $this->prefix ) ?></option>
						<?php endforeach; ?>
							<option value="random" <?php if ( $options['method'] == 'random' ) echo 'selected="selected"' ?>><?php _e( 'random', $this->prefix ) ?></option>
						</select></td>
					</tr>
					<tr>
						<th><label for="<?php echo $this->prefix ?>options[encode_display]"><?php _e( 'Encode email titles (display)', $this->prefix ) ?></label></th>
						<td><input type="checkbox" id="<?php echo $this->prefix ?>options[encode_display]" name="<?php echo $this->prefix ?>options[encode_display]" value="1" <?php checked('1', (int) $options['encode_display']); ?> /> <span class="description"><?php _e( '(recommended)', $this->prefix ) ?></span></td>
					</tr>
					<tr>
						<th><label for="<?php echo $this->prefix ?>options[encode_mailto]"><?php _e( 'Encode mailto links', $this->prefix ) ?></label></th>
						<td><input type="checkbox" id="<?php echo $this->prefix ?>options[encode_mailto]" name="<?php echo $this->prefix ?>options[encode_mailto]" value="1" <?php checked('1', (int) $options['encode_mailto']); ?> /> <span class="description"><?php _e( 'encode all mailto links in the content', $this->prefix ) ?></span></td>
					</tr>
					<tr>
						<th><label for="<?php echo $this->prefix ?>options[replace_emails]"><?php _e( 'Replace plain text emails', $this->prefix ) ?></label></th>
						<td><input type="checkbox" id="<?php echo $this->prefix ?>options[replace_emails]" name="<?php echo $this->prefix ?>options[replace_emails]" value="1" <?php checked('1', (int) $options['replace_emails']); ?> /> <span class="description"><?php _e( 'replacing plain text emails in the content to an encoded mailto link', $this->prefix ) ?></span></td>
					</tr>
					<tr>
						<th><label for="<?php echo $this->prefix ?>options[filter_comments]"><?php _e( 'Also filter comments', $this->prefix ) ?></label></th>
						<td><input type="checkbox" id="<?php echo $this->prefix ?>options[filter_comments]" name="<?php echo $this->prefix ?>options[filter_comments]" value="1" <?php checked('1', (int) $options['filter_comments']); ?> /> <span class="description"><?php _e( 'also filter all comments for encoding', $this->prefix ) ?></span></td>
					</tr>
					<tr>
						<th style="padding-top:25px"><label for="<?php echo $this->prefix ?>options[form_on_site]"><?php _e( 'Encode form on your site', $this->prefix ) ?></label></th>
						<td style="padding-top:25px"><input type="checkbox" id="<?php echo $this->prefix ?>options[form_on_site]" name="<?php echo $this->prefix ?>options[form_on_site]" value="1" <?php checked('1', (int) $options['form_on_site']); ?> /> <span class="description"><?php _e( 'put an encode form on your site, use this tag in a post or page:', $this->prefix ) ?></span> <code>[email_encoder_form]</code></td>
					</tr>
					<tr>
						<th><label for="<?php echo $this->prefix ?>options[powered_by]"><?php _e( 'Show powered by link', $this->prefix ) ?></label></th>
						<td><input type="checkbox" id="<?php echo $this->prefix ?>options[powered_by]" name="<?php echo $this->prefix ?>options[powered_by]" value="1" <?php checked('1', (int) $options['powered_by']); ?> /> <span class="description"><?php _e( 'show the powered by link on bottom of the encode form', $this->prefix ) ?></span></td>
					</tr>
					</table>
				</fieldset>
				<p class="submit">
					<input class="button-primary" type="submit" value="<?php _e( 'Save Changes', $this->prefix ) ?>" />
				</p>
			</form>
		</div>

		<div>
			<h3>How To Use?</h3>
			<p>Inside a post or page use this code: <code>[encode_email email="info@myemail.com" display="My Email"]</code></p>
			<p>And for templates use: <code>&lt;?php echo encode_email( 'info@myemail.com', 'My Email' ); ?&gt;</code></p>
		</div>

		<div>
			<h3>Encoder Form</h3>
			<?php echo $this->get_encoder_form(); ?>
		</div>

	</div>
<?php
	}

	/**
	 * Get the encoder form (to use as a demo, like on the options page)
	 * @return string
	 */
	function get_encoder_form() {
		ob_start();
?>
			<form class="email-encoder-form">
			<fieldset>
				<table class="form-table">
				<tr>
					<tr>
						<th><label for="email"><?php _e( 'Email', $this->prefix ) ?></label></th>
						<td><input type="text" class="regular-text" id="email" name="email" /></td>
					</tr>
					<tr>
						<th><label for="display"><?php _e( 'Display (optional)', $this->prefix ) ?></label></th>
						<td><input type="text" class="regular-text" id="display" name="display" /></td>
					</tr>
					<tr>
						<th><label for="encode_method"><?php _e( 'Encode method', $this->prefix ) ?></label></th>
						<td><select id="encode_method" name="encode_method" class="postform">
						<?php foreach ( $this->methods AS $key => $method ): ?>
							<option value="<?php echo $key ?>" <?php if ( $this->options['method'] == $key ) echo 'selected="selected"' ?>><?php _e( $key, $this->prefix ) ?></option>
						<?php endforeach; ?>
							<option value="random" <?php if ( $this->options['method'] == 'random' ) echo 'selected="selected"' ?>><?php _e( 'random', $this->prefix ) ?></option>
						</select>
							<input type="button" id="ajax_encode" value="Encode &gt;" /></td>
					</tr>
				</tr>
				<tr>
					<tr>
						<th><?php _e( 'Example', $this->prefix ) ?></th>
						<td><span id="example"></span></td>
					</tr>
					<tr>
						<th><label for="encoded_output"><?php _e( 'Code', $this->prefix ) ?></label></th>
						<td><textarea class="large-text node" id="encoded_output" name="encoded_output"></textarea></td>
					</tr>
				</tr>
				</table>
			<?php if ( $this->options['powered_by'] ): ?>
				<p class="powered-by">Powered by <a rel="external" href="http://www.freelancephp.net/email-encoder/">Email Encoder Bundle</a></p>
			<?php endif; ?>
			</fieldset>
			</form>
<?php
		$form = ob_get_contents();
		ob_clean();

		return $form;
	}

	/**
	 * Deactivation plugin method
	 */
	function deactivation() {
		delete_option( $this->prefix . 'options' );
		unregister_setting( $this->prefix, $this->prefix . 'options' );
	}

	/**
	 * Set options from save values or defaults
	 */
	function _set_options() {
		// set options
		$saved_options = get_option( $this->prefix . 'options' );
		if ( empty( $saved_options ) ) {
			// set defaults
			$this->options['encode_display'] = (int) $this->options['encode_display'];
			$this->options['encode_mailto'] = (int) $this->options['encode_mailto'];
			$this->options['replace_emails'] = (int) $this->options['replace_emails'];
			$this->options['filter_comments'] = (int) $this->options['filter_comments'];
			$this->options['form_on_site'] = (int) $this->options['form_on_site'];
			$this->options['powered_by'] = (int) $this->options['powered_by'];
		} else {
			// set saved option values
			$this->set_method( $saved_options['method'] );
			$this->options['encode_display'] = ! empty( $saved_options['encode_display'] );
			$this->options['encode_mailto'] = ! empty( $saved_options['encode_mailto'] );
			$this->options['replace_emails'] = ! empty( $saved_options['replace_emails'] );
			$this->options['filter_comments'] = ! empty( $saved_options['filter_comments'] );
			$this->options['form_on_site'] = ! empty( $saved_options['form_on_site'] );
			$this->options['powered_by'] = ! empty( $saved_options['powered_by'] );
		}
	}

} // end class WP_Email_Encoder


/**
 * Create instance
 */
$WP_Email_Encoder = new WP_Email_Encoder;


/**
 * Ajax request
 */
if ( ! empty( $_GET['ajax'] ) ):
	// input vars
	$method = $_GET['method'];
	$email = $_GET['email'];
	$display = ( empty( $_GET['display'] ) ) ? $email : $_GET['display'];

	$WP_Email_Encoder->set_method( $method );

	echo $WP_Email_Encoder->encode_email( $email, $display );
	exit;
endif;


/**
 * Template function for encoding email
 * @global WP_Email_Encoder $WP_Email_Encoder
 * @param string $email
 * @param string $display  if non given will be same as email
 * @return string
 */
if ( ! function_exists( 'encode_email' )  ):
	function encode_email( $email, $display = NULL ) {
		global $WP_Email_Encoder;
		return $WP_Email_Encoder->encode_email( $email, $display );
	}
endif;

?>