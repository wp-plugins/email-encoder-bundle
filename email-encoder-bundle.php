<?php
/*
Plugin Name: Email Encoder Bundle
Plugin URI: http://www.freelancephp.net/email-encoder-php-class-wp-plugin/
Description: Protect email addresses on your site from spambots and being used for spamming by using one of the encoding methods.
Author: Victor Villaverde Laan
Version: 0.30
Author URI: http://www.freelancephp.net
License: Dual licensed under the MIT and GPL licenses
*/
// include parent class
require_once dirname( __FILE__ ) . '/Lim_Email_Encoder.php';

/**
 * Class WP_Email_Encoder_Bundle, child of Lim_Email_Encoder
 * @package Lim_Email_Encoder
 * @category WordPress Plugins
 */
class WP_Email_Encoder_Bundle extends Lim_Email_Encoder {

	/**
	 * Current version
	 * @var string
	 */
	var $version = '0.30';

	/**
	 * Used as prefix for options entry and could be used as text domain (for translations)
	 * @var string
	 */
	var $domain = 'WP_Email_Encoder_Bundle';

	/**
	 * Name of the options
	 * @var string
	 */
	var $options_name = 'WP_Email_Encoder_Bundle_options';

	/**
	 * @var array
	 */
	var $options = array(
			'method' => NULL,
			'encode_mailtos' => 1,
			'encode_emails' => 1,
			'filter_widgets' => 1,
			'filter_comments' => 1,
			'filter_rss' => 1,
			'powered_by' => 1,
		);

	/**
	 * Regexp
	 * @var array
	 */
	var $regexp_patterns = array(
		'mailto' => '/<a.*?href=["\']mailto:(.*?)["\'].*?>(.*?)<\/a[\s+]*>/i',
		'tag' => '/\[encode_email\s+(.*?)\]/i',
		'email' => '/([A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6})/i',
	);

	/**
	 * PHP4 constructor
	 */
	function WP_Email_Encoder_Bundle() {
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

		// add actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'the_posts', array( &$this, 'the_posts' ) );
	}

	/**
	 * Callback init
	 */
	function init() {
		if ( is_admin() ) {
			// set uninstall hook
			if ( function_exists( 'register_deactivation_hook' ) )
				register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ));
		} else {
			$priority = 100;

			// set content filters
			add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ), $priotity );

			// comments
			if ( $this->options[ 'filter_comments' ] ) {
				add_filter( 'comment_text', array( $this, '_filter_callback' ), $priority );
				add_filter( 'comment_excerpt', array( $this, '_filter_callback' ), $priority );
				add_filter( 'get_comment_author_url', array( $this, '_filter_callback' ), $priority );
				add_filter( 'get_comment_author_link', array( $this, '_filter_callback' ), $priority );
				add_filter( 'get_comment_author_url_link', array( $this, '_filter_callback' ), $priority );
			}

			// widgets ( only text widgets )
			if ( $this->options[ 'filter_widgets' ] ) {
				add_filter( 'widget_title', array( $this, '_filter_callback' ), $priority );
				add_filter( 'widget_text', array( $this, '_filter_callback' ), $priority );

				// Only if Widget Logic plugin is installed
				add_filter( 'widget_content', array( $this, '_filter_callback' ), $priority );
			}
		}
	}

	/**
	 * pre_get_posts filter
	 * @param object $query
	 */
	function pre_get_posts( $query ) {
		$priority = 100;

		if ( $query->is_feed ) {
			// rss feed
			if ( $this->options[ 'filter_rss' ] ) {
				add_filter( 'the_content_rss', array( $this, '_filter_rss_callback' ), $priority );
				add_filter( 'the_content_feed', array( $this, '_filter_rss_callback' ), $priority );
				add_filter( 'the_excerpt_rss', array( $this, '_filter_rss_callback' ), $priority );
				add_filter( 'comment_text_rss', array( $this, '_filter_rss_callback' ), $priority );
			}
		} else {
			// post content
			add_filter( 'the_title', array( $this, '_filter_callback' ), $priority );
			add_filter( 'the_content', array( $this, '_filter_callback' ), $priority );
			add_filter( 'the_excerpt', array( $this, '_filter_callback' ), $priority );
			add_filter( 'get_the_excerpt', array( $this, '_filter_callback' ), $priority );
		}

		return $query;
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
				wp_enqueue_script( 'email_encoder', plugins_url( 'js/email-encoder-bundle.js', __FILE__ ), array( 'jquery' ), $this->version );
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
		}
	}

	/**
	 * Callback admin_init
	 */
	function admin_init() {
		// register settings
		register_setting( $this->domain, $this->options_name );

		// set dashboard postbox
		wp_admin_css( 'dashboard' );
		wp_enqueue_script( 'dashboard' );

		// add style and script for ajax encoder
		wp_enqueue_script( 'email_encoder', plugins_url( 'js/email-encoder-bundle.js', __FILE__ ), array( 'jquery' ), $this->version );
	}

	/**
	 * Admin options page
	 */
	function options_page() {
?>
<script language="javascript">
jQuery(function( $ ){
	// remove message
	$( '.settings-error' )
		.hide()
		.slideDown( 600 )
		.delay( 3000 )
		.slideUp( 600 );

	// set info text for selected encoding method
	$( '.method-info-select' ).bind( 'change blur keyup', function(){
			var method = $( this ).val(),
				$desc = $( this ).parent().find( 'span.description' );

			if ( methodInfo && methodInfo[ method ] ) {
				$desc.html( methodInfo[ method ][ 'description' ] || '' );
			} else {
				$desc.html( '' );
			}
		})
		.blur();

	// add form-table class to Encoder Form tables
	$( '.email-encoder-form table' ).addClass( 'form-table' );
});
</script>
	<div class="wrap">
		<div class="icon32" id="icon-options-custom" style="background:url( <?php echo plugins_url( 'images/icon-email-encoder-bundle.png', __FILE__ ) ?> ) no-repeat 50% 50%"><br></div>
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
		<div style="margin:0 5px;">
			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'General Settings' ) ?></h3>
				<div class="inside">
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e( 'Choose encoding method', $this->domain ) ?></th>
							<td><label><select id="<?php echo $this->options_name ?>[method]" name="<?php echo $this->options_name ?>[method]" class="method-info-select postform">
								<?php foreach ( $this->methods AS $method => $info ): ?>
									<option value="<?php echo $method ?>" <?php if ( $this->method == $method ) echo 'selected="selected"' ?>><?php echo $info[ 'name' ] ?></option>
								<?php endforeach; ?>
									<option value="random" <?php if ( $this->method == 'random' ) echo 'selected="selected"' ?>><?php echo __( 'Random', $this->domain ) ?></option>
								</select>
								<br /><span class="description"></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Encode emails', $this->domain ) ?></th>
							<td>
								<label><input type="checkbox" name="<?php echo $this->options_name ?>[encode_tags]" value="1" checked="checked" disabled="disabled" />
									<span><?php _e( 'Encode <code>[encode_email]</code> tags', $this->domain ) ?></span>
								</label>
							<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[encode_mailtos]" name="<?php echo $this->options_name ?>[encode_mailtos]" value="1" <?php checked('1', (int) $options['encode_mailtos']); ?> />
									<span><?php _e( 'Encode mailto-links', $this->domain ) ?></span>
								</label>
							<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[encode_emails]" name="<?php echo $this->options_name ?>[encode_emails]" value="1" <?php checked('1', (int) $options['encode_emails']); ?> />
									<span><?php _e( 'Replace plain emailaddresses to encoded mailto-links', $this->domain ) ?></span>
								</label>
								<?php if ( $enc_conflict ): ?>
									<br/><span class="description"><?php _e( 'Warning: "WP Mailto Links Plugin" is activated, turn off this option when a conflict occures.', $this->domain ) ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Options has effect on', $this->domain ) ?></th>
							<td><label><input type="checkbox" name="<?php echo $this->options_name ?>[filter_posts]" value="1" checked="checked" disabled="disabled" />
										<span><?php _e( 'Posts', $this->domain ) ?></span>
									</label>
								<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[filter_comments]" name="<?php echo $this->options_name ?>[filter_comments]" value="1" <?php checked('1', (int) $options['filter_comments']); ?> />
									<span><?php _e( 'Comments', $this->domain ) ?></span></label>
								<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[filter_widgets]" name="<?php echo $this->options_name ?>[filter_widgets]" value="1" <?php checked('1', (int) $options['filter_widgets']); ?> />
									<span><?php _e( 'Text widgets', $this->domain ) ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Protect RSS feed', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[filter_rss]" name="<?php echo $this->options_name ?>[filter_rss]" value="1" <?php checked('1', (int) $options['filter_rss']); ?> />
									<span><?php _e( 'Replace emails in RSS feed by <code>*protected email*</code>', $this->domain ) ?></span></label>
							</td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e( 'Save Changes' ) ?>" />
					</p>
				</div>
			</div>

			<div class="postbox closed">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'How to use', $this->domain ) ?></h3>
				<div class="inside">
					<h4><?php _e( 'Tags', $this->domain ) ?></h4>
					<ul>
						<li><code>[encode_email email="..." display="..."]</code> <span class="description"><?php _e( 'Encode the given email, "display" is optional otherwise the email wil be used as display', $this->domain ) ?></span></li>
						<li><code>[email_encoder_form]</code> <span class="description"><?php _e( 'Puts an email encoder form in your post', $this->domain ) ?></span></li>
					</ul>
					<h4><?php _e( 'Template functions' ) ?></h4>
					<ul>
						<li><code>&lt;?php echo encode_email( 'info@myemail.com', 'My Email' ); ?&gt;</code> <span class="description"><?php _e( 'Encode the given email, the second param is display and optional', $this->domain ) ?></span></li>
						<li><code>&lt;?php echo encode_email_filter( $content ); ?&gt;</code> <span class="description"><?php _e( 'Filter the given content for emails to encode', $this->domain ) ?></span></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Email Encoder Form', $this->domain ) ?></h3>
				<div class="inside">
					<?php echo $this->get_encoder_form(); ?>

					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e( 'Show "powered by"-link', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[powered_by]" name="<?php echo $this->options_name ?>[powered_by]" value="1" <?php checked('1', (int) $options['powered_by']); ?> /> <span><?php _e( 'Show the "powered by"-link on bottom of the encode form', $this->domain ) ?></span></label></td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e( 'Save Changes' ) ?>" />
					</p>
				</div>
			</div>
		</div>
		</div>

		<div class="postbox-container side metabox-holder meta-box-sortables" style="width:29%;">
		<div style="margin:0 5px;">
			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'About' ) ?>...</h3>
				<div class="inside">
					<h4><img src="<?php echo plugins_url( 'images/icon-email-encoder-bundle.png', __FILE__ ) ?>" width="16" height="16" /> Email Encoder Bundle (v<?php echo $this->version ?>)</h4>
					<p><?php _e( 'Protect email addresses on your site from spambots and being used for spamming by using one of the encoding methods.', $this->domain ) ?></p>
					<ul>
						<li><a href="http://www.freelancephp.net/contact/" target="_blank"><?php _e( 'Questions or suggestions?', $this->domain ) ?></a></li>
						<li><?php _e( 'If you like this plugin please send your rating at WordPress.org.' ) ?></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br/></div>
				<h3 class="hndle"><?php _e( 'Other Plugins', $this->domain ) ?></h3>
				<div class="inside">
					<h4><img src="<?php echo plugins_url( 'images/icon-wp-external-links.png', __FILE__ ) ?>" width="16" height="16" /> WP External Links</h4>
					<p><?php _e( 'Manage external links on your site: open in new window/tab, set icon, add "external", add "nofollow" and more.', $this->domain ) ?></p>
					<ul>
						<li><a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+External+Links+freelancephp&plugin-search-input=Search+Plugins" target="_blank"><?php _e( 'Get this plugin now' ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/wp-external-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-external-links-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>

					<h4><img src="<?php echo plugins_url( 'images/icon-wp-mailto-links.png', __FILE__ ) ?>" width="16" height="16" /> WP Mailto Links</h4>
					<p><?php _e( 'Manage mailto links on your site and protect emails from spambots, set mail icon and more.', $this->domain ) ?></p>
					<ul>
						<li><a href="<?php echo get_bloginfo( 'url' ) ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+Mailto+Links+freelancephp&plugin-search-input=Search+Plugins" target="_blank"><?php _e( 'Get this plugin now' ) ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/wp-mailto-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-mailto-links-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>
				</div>
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
	<div class="email-encoder-form">
		<form>
			<fieldset>
				<div class="input">
					<table>
					<tr>
						<tr>
							<th><label for="email"><?php _e( 'Email address', $this->domain ) ?></label></th>
							<td><input type="text" class="regular-text" id="email" name="email" /></td>
						</tr>
						<tr>
							<th><label for="display"><?php _e( 'Display', $this->domain ) ?></label></th>
							<td><input type="text" class="regular-text" id="display" name="display" /></td>
						</tr>
						<tr>
							<th><?php _e( 'Example', $this->domain ) ?></th>
							<td><span id="example"></span></td>
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
				</div>
				<div class="output nodis">
					<table>
					<tr>
						<tr>
							<th><label for="encoded_output"><?php _e( 'Code', $this->domain ) ?></label></th>
							<td><textarea class="large-text node" id="encoded_output" name="encoded_output"></textarea></td>
						</tr>
					</tr>
					</table>
				</div>
			<?php if ( $this->options['powered_by'] ): ?>
				<p class="powered-by"><?php _e( 'Powered by', $this->domain ) ?> <a rel="external" href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/">Email Encoder Bundle</a></p>
			<?php endif; ?>
			</fieldset>
		</form>
	</div>
<?php
		$form = ob_get_contents();
		ob_clean();

		return $form;
	}

	/**
	 * Encode all emails of the given content
	 * @param string $content
	 * @param boolean $enc_tags Optional, default TRUE
	 * @param boolean $enc_mailtos  Optional, default TRUE
	 * @param boolean $enc_plain_emails Optional, default TRUE
	 * @return string
	 */
	function filter( $content, $enc_tags = TRUE, $enc_mailtos = TRUE, $enc_plain_emails = TRUE ) {
		// encode mailto links
		if ( $enc_mailtos )
			$content = preg_replace_callback( $this->regexp_patterns[ 'mailto' ], array( $this, '_callback' ), $content );

		// replace content tags [encode_email] to mailto links
		if ( $enc_tags )
			$content = preg_replace_callback( $this->regexp_patterns[ 'tag' ], array( $this, '_callback_shortcode' ), $content );

		// replace plain emails
		if ( $enc_plain_emails )
			$content = preg_replace_callback( $this->regexp_patterns[ 'email' ], array( $this, '_callback' ), $content );

		return $content;
	}

	/**
	 * Deactivation plugin method
	 */
	function deactivation() {
		delete_option( $this->options_name );
		unregister_setting( $this->domain, $this->options_name );
	}

	/**
	 * Set options from save values or defaults
	 */
	function _set_options() {
		// set options
		$saved_options = get_option( $this->options_name );

		// backwards compatible (old values)
		if ( empty( $saved_options ) ) {
			$saved_options = get_option( $this->domain . 'options' );
		}

		// set all options
		if ( ! empty( $saved_options ) ) {
			foreach ( $this->options AS $key => $option ) {
				$this->options[ $key ] = $saved_options[ $key ];
			}
		}

		$this->set_method( $this->options['method'] );
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
	 * Callback shortcode [encode_email ... ] for encoding email
	 * @param array $match
	 * @return string
	 */
	function _callback_shortcode( $match ) {
		$attrs = shortcode_parse_atts( $match[1] );

		if ( key_exists( 'email', $attrs ) )
			$email = $attrs[ 'email' ];

		if ( key_exists( 'display', $attrs ) ) {
			$display = $attrs[ 'display' ];
		} else {
			$display = $attrs[ 'email' ];
		}

		return $this->encode( $email, $display );
	}

	/**
	 * Callback used for wp filters
	 */
	function _filter_callback( $content ) {
		return $this->filter( $content, TRUE, $this->options[ 'encode_mailtos' ], $this->options[ 'encode_emails' ] );
	}

	/**
	 * Callback RSS
	 */
	function _filter_rss_callback( $content ) {
		return preg_replace( $this->regexp_patterns, '*protected email*', $content );
	}

} // end class WP_Email_Encoder_Bundle


/**
 * Create instance
 */
$WP_Email_Encoder_Bundle = new WP_Email_Encoder_Bundle;


/**
 * Ajax Encoding request
 */
if ( ! empty( $_GET['ajax'] ) ):
	// input vars
	$method = $_GET['method'];
	$email = $_GET['email'];
	$display = ( empty( $_GET['display'] ) ) ? $email : $_GET['display'];

	$WP_Email_Encoder_Bundle->set_method( $method );

	echo $WP_Email_Encoder_Bundle->encode( $email, $display );
	exit;
endif;


/**
 * Template function for encoding email
 * @global WP_Email_Encoder $WP_Email_Encoder_Bundle
 * @param string $email
 * @param string $display  if non given will be same as email
 * @return string
 */
if ( ! function_exists( 'encode_email' )  ):
	function encode_email( $email, $display = NULL ) {
		global $WP_Email_Encoder_Bundle;
		return $WP_Email_Encoder_Bundle->encode( $email, $display );
	}
endif;

/**
 * Template function for encoding emails in the given content
 * @global WP_Email_Encoder $WP_Email_Encoder_Bundle
 * @param string $content
 * @param boolean $enc_tags Optional, default TRUE
 * @param boolean $enc_mailtos  Optional, default TRUE
 * @param boolean $enc_plain_emails Optional, default TRUE
 * @return string
 */
if ( ! function_exists( 'encode_email_filter' )  ):
	function encode_email_filter( $content, $enc_tags = TRUE, $enc_mailtos = TRUE, $enc_plain_emails = TRUE  ) {
		global $WP_Email_Encoder_Bundle;
		return $WP_Email_Encoder_Bundle->filter( $content, $enc_tags, $enc_mailtos, $enc_plain_emails );
	}
endif;

?>