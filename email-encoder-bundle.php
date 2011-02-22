<?php
/*
Plugin Name: Email Encoder Bundle
Plugin URI: http://www.freelancephp.net/email-encoder-php-class-wp-plugin/
Description: Protecting email-spamming by replacing them with one of the registered encoding-methods
Author: Victor Villaverde Laan
Version: 0.2
Author URI: http://www.freelancephp.net
License: Dual licensed under the MIT and GPL licenses
*/
// include parent class
require_once dirname( __FILE__ ) . '/Lim_Email_Encoder.php';

/**
 * Class WP_Email_Encoder, child of Lim_Email_Encoder
 * @package Lim_Email_Encoder
 * @category WordPress Plugins
 */
class WP_Email_Encoder extends Lim_Email_Encoder {

	/**
	 * Used as prefix for options entry and could be used as text domain (for translations)
	 * @var string
	 */
	var $domain = 'wp_email_enc';

	/**
	 * @var array
	 */
	var $options = array(
			'filter_widgets' => TRUE,
			'filter_comments' => TRUE,
			'form_on_site' => FALSE, // set encoder form on the website
			'powered_by' => TRUE,
			'encode_tags' => TRUE,
			'encode_mailtos' => TRUE,
			'encode_emails' => TRUE,
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

		// set option values
		$this->_set_options();
		
		// load text domain for translations
		load_plugin_textdomain( $this->domain, dirname( __FILE__ ) . '/lang/', basename( dirname(__FILE__) ) . '/lang/' );

		// add filters
		add_filter( 'the_content', array( &$this, '_filter_callback' ), 100 );

		// also filter comments
		if ( $this->options['filter_comments'] )
			add_filter( 'comment_text', array( &$this, '_filter_callback' ), 100 );

		// also filter widgets
		if ( $this->options['filter_widgets'] )
			add_filter( 'widget_text', array( &$this, '_filter_callback' ), 100 );

		// add actions
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
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
		if ( empty( $posts ) OR ! $this->options['form_on_site'] )
			return $posts;

		foreach ( $posts as $key => $post ) {
			if ( stripos( $post->post_content, '[email_encoder_form]' ) > -1 ) {
				// add style and script for ajax encoder
				wp_enqueue_script( 'email_encoder', plugins_url( 'js/email-encoder-bundle.js', __FILE__ ), array( 'jquery' ) );
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
		register_setting( $this->domain, $this->domain . 'options' );
	}

	/**
	 * Callback admin_print_scripts
	 */
	function admin_print_scripts() {
		// add script for ajax encoder
		wp_enqueue_script( 'email_encoder', plugins_url( 'js/email-encoder-bundle.js', __FILE__ ), array( 'jquery-ui-sortable' ) );
	}

	/**
	 * Admin options page
	 */
	function options_page() {
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Email Encoder Bundle</h2>

		<form method="post" action="options.php">
			<script language="javascript">
				var methodInfo = <?php echo json_encode( $this->methods ) ?>;
			</script>
			<?php
				settings_fields( $this->domain );
				$this->_set_options();
				$options = $this->options;
			?>
		<div class="postbox-container metabox-holder meta-box-sortables" style="width: 69%">
			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Settings' ) ?></h3>
				<div class="inside">
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><label for="<?php echo $this->domain ?>options[method]"><?php _e( 'Choose encoding method', $this->domain ) ?></label></th>
							<td><select id="<?php echo $this->domain ?>options[method]" name="<?php echo $this->domain ?>options[method]" class="method-info-select postform">
								<?php foreach ( $this->methods AS $method => $info ): ?>
									<option value="<?php echo $method ?>" <?php if ( $this->method == $method ) echo 'selected="selected"' ?>><?php echo $info[ 'name' ] ?></option>
								<?php endforeach; ?>
									<option value="random" <?php if ( $this->method == 'random' ) echo 'selected="selected"' ?>><?php echo __( 'Random', $this->domain ) ?></option>
								</select>
								<br /><span class="description"></span>
							</td>
						</tr>
						<tr>
							<th><label for="<?php echo $this->domain ?>options[encode_tags]"><?php _e( 'Encode tags', $this->domain ) ?></label></th>
							<td><input type="checkbox" id="<?php echo $this->domain ?>options[encode_tags]" name="<?php echo $this->domain ?>options[encode_tags]" value="1" <?php checked('1', (int) $options['encode_tags']); ?> /> <span class="description"><?php _e( 'Encode all tags like', $this->domain ) ?></span> <code>[encode_email email="info@myemail.com" display="My Email"]</code></td>
						</tr>
						<tr>
							<th><label for="<?php echo $this->domain ?>options[encode_mailtos]"><?php _e( 'Encode mailto links', $this->domain ) ?></label></th>
							<td><input type="checkbox" id="<?php echo $this->domain ?>options[encode_mailtos]" name="<?php echo $this->domain ?>options[encode_mailtos]" value="1" <?php checked('1', (int) $options['encode_mailtos']); ?> /> <span class="description"><?php _e( 'Also encode mailto links', $this->domain ) ?></span></td>
						</tr>
						<tr>
							<th><label for="<?php echo $this->domain ?>options[encode_emails]"><?php _e( 'Encode plain emails', $this->domain ) ?></label></th>
							<td><input type="checkbox" id="<?php echo $this->domain ?>options[encode_emails]" name="<?php echo $this->domain ?>options[encode_emails]" value="1" <?php checked('1', (int) $options['encode_emails']); ?> /> <span class="description"><?php _e( 'Replacing plain text emails to an encoded mailto link', $this->domain ) ?></span></td>
						</tr>
						<tr>
							<th style="padding-top:25px"><label for="<?php echo $this->domain ?>options[filter_comments]"><?php _e( 'Include comments', $this->domain ) ?></label></th>
							<td style="padding-top:25px"><input type="checkbox" id="<?php echo $this->domain ?>options[filter_comments]" name="<?php echo $this->domain ?>options[filter_comments]" value="1" <?php checked('1', (int) $options['filter_comments']); ?> /> <span class="description"><?php _e( 'Also filter all comments for encoding', $this->domain ) ?></span></td>
						</tr>
						<tr>
							<th><label for="<?php echo $this->domain ?>options[filter_widgets]"><?php _e( 'Include widgets', $this->domain ) ?></label></th>
							<td><input type="checkbox" id="<?php echo $this->domain ?>options[filter_widgets]" name="<?php echo $this->domain ?>options[filter_widgets]" value="1" <?php checked('1', (int) $options['filter_widgets']); ?> /> <span class="description"><?php _e( 'Also filter widgets for encoding', $this->domain ) ?></span></td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e( 'Save Changes' ) ?>" />
					</p>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Encoder Form', $this->domain ) ?></h3>
				<div class="inside">
					<?php echo $this->get_encoder_form(); ?>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Settings Encoder Form', $this->domain ) ?></h3>
				<div class="inside">
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><label for="<?php echo $this->domain ?>options[form_on_site]"><?php _e( 'Encode form on your site', $this->domain ) ?></label></th>
							<td><input type="checkbox" id="<?php echo $this->domain ?>options[form_on_site]" name="<?php echo $this->domain ?>options[form_on_site]" value="1" <?php checked('1', (int) $options['form_on_site']); ?> /> <span class="description"><?php _e( 'Put an encode form (like above) on your site by using this tag in a post or page', $this->domain ) ?></span> <code>[email_encoder_form]</code><span class="description"> (<?php _e( 'turn off for when not used', $this->domain ) ?>)</span></td>
						</tr>
						<tr>
							<th><label for="<?php echo $this->domain ?>options[powered_by]"><?php _e( 'Show "powered by"-link', $this->domain ) ?></label></th>
							<td><input type="checkbox" id="<?php echo $this->domain ?>options[powered_by]" name="<?php echo $this->domain ?>options[powered_by]" value="1" <?php checked('1', (int) $options['powered_by']); ?> /> <span class="description"><?php _e( 'Show the "powered by"-link on bottom of the encode form', $this->domain ) ?></span></td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e( 'Save Changes' ) ?>" />
					</p>
				</div>
			</div>
		</div>

		<div class="postbox-container side metabox-holder meta-box-sortables" style="width: 29%">
			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'How to use', $this->domain ) ?></h3>
				<div class="inside">
					<h4><?php _e( 'Tags', $this->domain ) ?></h4>
					<ul>
						<li><code>[encode_email email="..." display="..."]</code><br/><span class="description"><?php _e( 'Encode the given email, "display" is optional otherwise the email wil be used as display', $this->domain ) ?></span></li>
						<li><code>[email_encoder_form]</code><br/><span class="description"><?php _e( 'Puts an encoder form in your post (check if the option is activated on this page)', $this->domain ) ?></span></li>
					</ul>
					<h4><?php _e( 'Template functions' ) ?></h4>
					<ul>
						<li><code>&lt;?php echo encode_email( 'info@myemail.com', 'My Email' ); ?&gt;</code><br/><span class="description"><?php _e( 'Encode the given email, the second param is display and optional', $this->domain ) ?></span></li>
						<li><code>&lt;?php echo encode_email_filter( $content ); ?&gt;</code><br/><span class="description"><?php _e( 'Filter the given content for emails to encode', $this->domain ) ?></span></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'About this plugin', $this->domain ) ?></h3>
				<div class="inside">
					<h4>FreelancePHP.net</h4>
					<ul>
						<li><a href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/" target="_blank">WP Email Encoder Bundle</a></li>
					</ul>

					<h4>WordPress Plugin Directory</h4>
					<ul>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/" target="_blank"><?php _e( 'Description', $this->domain ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/installation/" target="_blank"><?php _e( 'Installation', $this->domain ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/faq/" target="_blank"><?php _e( 'FAQ', $this->domain ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/screenshots/" target="_blank"><?php _e( 'Screenshot', $this->domain ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/other_notes/" target="_blank"><?php _e( 'Other Notes', $this->domain ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/changelog/" target="_blank"><?php _e( 'Changelog', $this->domain ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/stats/" target="_blank"><?php _e( 'Stats', $this->domain ) ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		</form>
		<div class="clear"></div>
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
						<th><label for="email"><?php _e( 'Email', $this->domain ) ?></label></th>
						<td><input type="text" class="regular-text" id="email" name="email" /></td>
					</tr>
					<tr>
						<th><label for="display"><?php _e( 'Display (optional)', $this->domain ) ?></label></th>
						<td><input type="text" class="regular-text" id="display" name="display" /></td>
					</tr>
					<tr>
						<th><label for="encode_method"><?php _e( 'Encode method', $this->domain ) ?></label></th>
						<td><select id="encode_method" name="encode_method" class="postform">
							<?php foreach ( $this->methods AS $method => $info ): ?>
								<option value="<?php echo $method ?>" <?php if ( $this->method == $method ) echo 'selected="selected"' ?>><?php echo $info[ 'name' ] ?></option>
							<?php endforeach; ?>
								<option value="random" <?php if ( $this->method == 'random' ) echo 'selected="selected"' ?>><?php _e( 'Random', $this->domain ) ?></option>
							</select>
							<input type="button" id="ajax_encode" value="<?php _e( 'Encode', $this->domain ) ?> &gt;&gt;" />
						</td>
					</tr>
				</tr>
				</table>
				<hr />
				<table class="form-table">
				<tr>
					<tr>
						<th><?php _e( 'Link', $this->domain ) ?></th>
						<td><span id="example"></span></td>
					</tr>
					<tr>
						<th><label for="encoded_output"><?php _e( 'Code', $this->domain ) ?></label></th>
						<td><textarea class="large-text node" id="encoded_output" name="encoded_output"></textarea></td>
					</tr>
				</tr>
				</table>
			<?php if ( $this->options['powered_by'] ): ?>
				<p class="powered-by"><?php _e( 'Powered by', $this->domain ) ?> <a rel="external" href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/">Email Encoder Bundle</a></p>
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
		delete_option( $this->domain . 'options' );
		unregister_setting( $this->domain, $this->domain . 'options' );
	}

	/**
	 * Set options from save values or defaults
	 */
	function _set_options() {
		// set options
		$saved_options = get_option( $this->domain . 'options' );
		if ( empty( $saved_options ) ) {
			// set defaults
			$this->options['encode_tags'] = (int) $this->options['encode_tags'];
			$this->options['encode_mailtos'] = (int) $this->options['encode_mailtos'];
			$this->options['encode_emails'] = (int) $this->options['encode_emails'];
			$this->options['filter_comments'] = (int) $this->options['filter_comments'];
			$this->options['filter_widgets'] = (int) $this->options['filter_widgets'];
			$this->options['form_on_site'] = (int) $this->options['form_on_site'];
			$this->options['powered_by'] = (int) $this->options['powered_by'];
		} else {
			// set saved option values
			$this->set_method( $saved_options['method'] );
			$this->options['encode_tags'] = ! empty( $saved_options['encode_tags'] );
			$this->options['encode_mailtos'] = ! empty( $saved_options['encode_mailtos'] );
			$this->options['encode_emails'] = ! empty( $saved_options['encode_emails'] );
			$this->options['filter_comments'] = ! empty( $saved_options['filter_comments'] );
			$this->options['filter_widgets'] = ! empty( $saved_options['filter_widgets'] );
			$this->options['form_on_site'] = ! empty( $saved_options['form_on_site'] );
			$this->options['powered_by'] = ! empty( $saved_options['powered_by'] );
		}
	}

	/**
	 * Callback used for wp filters
	 */
	function _filter_callback( $content ) {
		return $this->filter( $content, $this->options[ 'encode_tags' ], $this->options[ 'encode_mailtos' ], $this->options[ 'encode_emails' ] );
	}

} // end class WP_Email_Encoder


/**
 * Create instance
 */
$WP_Email_Encoder = new WP_Email_Encoder;


/**
 * Ajax Encoding request
 */
if ( ! empty( $_GET['ajax'] ) ):
	// input vars
	$method = $_GET['method'];
	$email = $_GET['email'];
	$display = ( empty( $_GET['display'] ) ) ? $email : $_GET['display'];

	$WP_Email_Encoder->set_method( $method );

	echo $WP_Email_Encoder->encode( $email, $display );
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
		return $WP_Email_Encoder->encode( $email, $display );
	}
endif;

/**
 * Template function for encoding emails in the given content
 * @global WP_Email_Encoder $WP_Email_Encoder
 * @param string $content
 * @param boolean $enc_tags Optional, default TRUE
 * @param boolean $enc_plain_emails Optional, default TRUE
 * @param boolean $enc_mailtos  Optional, default TRUE
 * @return string
 */
if ( ! function_exists( 'encode_email_filter' )  ):
	function encode_email_filter( $content, $enc_tags = TRUE, $enc_plain_emails = TRUE, $enc_mailtos = TRUE  ) {
		global $WP_Email_Encoder;
		return $WP_Email_Encoder->filter( $content, $enc_tags, $enc_plain_emails, $enc_mailtos );
	}
endif;

?>