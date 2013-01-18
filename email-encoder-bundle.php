<?php
/*
Plugin Name: Email Encoder Bundle
Plugin URI: http://www.freelancephp.net/email-encoder-php-class-wp-plugin/
Description: Protect email addresses on your site from spambots and being used for spamming by using one of the encoding methods.
Author: Victor Villaverde Laan
Version: 0.60
Author URI: http://www.freelancephp.net
License: Dual licensed under the MIT and GPL licenses
*/

/**
 * Class WP_Email_Encoder_Bundle
 * @package WP_Email_Encoder_Bundle
 * @category WordPress Plugins
 */
class WP_Email_Encoder_Bundle {

	/**
	 * Current version
	 * @var string
	 */
	var $version = '0.60';

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
		'method' => 'enc_ascii',
		'encode_mailtos' => 1,
		'encode_emails' => 0,
		'skip_posts' => '',
		'class_name' => 'mailto-link',
		'filter_posts' => 1,
		'filter_widgets' => 1,
		'filter_comments' => 1,
		'filter_rss' => 1,
		'protection_text' => '*protected email*',
		'widget_logic_filter' => 0,
		'powered_by' => 1,
	);

	/**
	 * @var array
	 */
	var $skip_posts = array();

	/**
	 * @var boolead
	 */
	var $logged_in = FALSE;

	/**
	 * @var string
	 */
	var $method = 'enc_ascii';

	/**
	 * @var array
	 */
	var $methods = array(
		'enc_ascii' => array(
			'name' => 'JavaScript ASCII',
			'description' => 'Uses javascript (<a href="http://rumkin.com/tools/mailto_encoder/" target="_blank">original source</a>).',
		),
		'enc_escape' => array(
			'name' => 'JavaScript Escape',
			'description' => 'Uses javascript eval() function (<a href="http://blueberryware.net/2008/09/14/email-spam-protection/" target="_blank">original source</a>).',
		),
		'enc_html' => array(
			'name' => 'Html Encode',
			'description' => 'Email encode method using antispambot() built-in WordPress (<a href="http://codex.wordpress.org/Function_Reference/antispambot" target="_blank">more info</a>).',
		),
	);

	/**
	 * Regexp
	 * @var array
	 */
	var $regexp_patterns = array(
		'mailto' => '/<a[^<>]*?href=["\']mailto:(.*?)["\'].*?>(.*?)<\/a[\s+]*>/is',
		'tag' => '/\[encode_email\s+(.*?)\]/is',
		'email' => '/([A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6})/is',
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
		// load text domain for translations
		load_plugin_textdomain($this->domain, FALSE, dirname(plugin_basename(__FILE__)) . '/lang/');

		// set option values
		$this->set_options();

		// prepare vars
		$skip_posts = $this->options['skip_posts'];
		$skip_posts = str_replace(' ', '', $skip_posts);
		$skip_posts = explode(',', $skip_posts);
		$this->skip_posts = $skip_posts;

		// set uninstall hook
		if (function_exists('register_deactivation_hook')) {
			register_deactivation_hook(__FILE__, array($this, 'deactivation'));
		}

		// add actions
		add_action('wp', array($this, 'wp'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	/**
	 * wp action
	 * @global type $user_ID
	 */
	function wp() {
		global $user_ID;
		$this->logged_in = (bool) ($user_ID && current_user_can('level_10'));

		$priority = 100;

		// shortcodes
		add_shortcode('email_encoder_form', array($this, 'shortcode_email_encoder_form'));
		add_shortcode('encode_email', array($this, 'shortcode_encode_email'));
		add_shortcode('encode_content', array($this, 'shortcode_encode_content'));

		if (is_feed()) {
			// rss feed
			if ($this->options['filter_rss']) {
				add_filter('the_title', array($this, 'filter_rss_callback'), $priority);
				add_filter('the_content', array($this, 'filter_rss_callback'), $priority);
				add_filter('the_excerpt', array($this, 'filter_rss_callback'), $priority);
				add_filter('the_title_rss', array($this, 'filter_rss_callback'), $priority);
				add_filter('the_content_rss', array($this, 'filter_rss_callback'), $priority);
				add_filter('the_excerpt_rss', array($this, 'filter_rss_callback'), $priority);
				add_filter('comment_text_rss', array($this, 'filter_rss_callback'), $priority);
				add_filter('comment_author_rss ', array($this, 'filter_rss_callback'), $priority);
				add_filter('the_category_rss ', array($this, 'filter_rss_callback'), $priority);
				add_filter('the_content_feed', array($this, 'filter_rss_callback'), $priority);
				add_filter('author feed link', array($this, 'filter_rss_callback'), $priority);
				add_filter('feed_link', array($this, 'filter_rss_callback'), $priority);
			}
		} else {
			// add style when logged in
			if ($this->logged_in) {
				add_action('wp_head', array($this, 'add_style'));
			}

			// post content
			if ($this->options['filter_posts']) {
				add_filter('the_title', array($this, 'filter_callback'), $priority);
				add_filter('the_content', array($this, 'filter_callback'), $priority);
				add_filter('the_excerpt', array($this, 'filter_callback'), $priority);
				add_filter('get_the_excerpt', array($this, 'filter_callback'), $priority);
			}

			// comments
			if ($this->options['filter_comments']) {
				add_filter('comment_text', array($this, 'filter_callback'), $priority);
				add_filter('comment_excerpt', array($this, 'filter_callback'), $priority);
				add_filter('comment_url', array($this, 'filter_callback'), $priority);
				add_filter('get_comment_author_url', array($this, 'filter_callback'), $priority);
				add_filter('get_comment_author_link', array($this, 'filter_callback'), $priority);
				add_filter('get_comment_author_url_link', array($this, 'filter_callback'), $priority);
			}

			// widgets
			if ($this->options['filter_widgets']) {
				// Only text widgets
				add_filter('widget_title', array($this, 'filter_callback'), $priority);
				add_filter('widget_text', array($this, 'filter_callback'), $priority);
				// also replace shortcodes
				add_filter('widget_text', 'do_shortcode', $priority);

				// Only if Widget Logic plugin is installed and 'widget_content' option is activated
				add_filter('widget_content', array($this, 'filter_callback'), $priority);
				// also replace shortcodes
				add_filter('widget_content', 'do_shortcode', $priority);
			}
		}

		// action hook
		do_action('init_email_encoder_bundle', array($this, 'filter_callback'));
	}

	/**
	 * Shortcode showing encoder form
	 * @return string
	 */
	function shortcode_email_encoder_form() {
		// add style and script for ajax encoder
		wp_enqueue_script('email_encoder', plugins_url('js/email-encoder-bundle.js', __FILE__), array('jquery'), $this->version);

		return $this->get_encoder_form();
	}

	/**
	 * Shortcode encoding email
	 * @param array $attrs
	 * @return string
	 */
	function shortcode_encode_email($attrs) {
		if (!key_exists('email', $attrs)) {
			return '';
		}

		$email = $attrs['email'];
		$display = (key_exists('display', $attrs)) ? $attrs['display'] : $attrs['email'];
		$method = (key_exists('method', $attrs)) ? $attrs['method'] : NULL;
		$extra_attrs = (key_exists('extra_attrs', $attrs)) ? $attrs['extra_attrs'] : NULL;

		return $this->encode_email($email, $display, $method, $extra_attrs);
	}

	/**
	 * Shortcode encoding content
	 * @param array $attrs
	 * @param string $content Optional
	 * @return string
	 */
	function shortcode_encode_content($attrs, $content = '') {
		$method = (key_exists('method', $attrs)) ? $attrs['method'] : NULL;

		return $this->encode($content, $method);
	}

	/**
	 * Add style for encoded mails when logged in
	 */
	function add_style() {
		echo '<style type="text/css">' . "\n";
		echo '.' . $this->domain . ' { background: #f00 !important; display: inline !important; }' . "\n";
		echo '</style>' . "\n";
	}

	/**
	 * WP filter
	 * @param string $content
	 * @return string
	 */
	function filter_callback($content) {
		global $post;

		if (isset($post) && in_array($post->ID, $this->skip_posts)) {
			return $content;
		}

		return $this->filter($content, TRUE, $this->options['encode_mailtos'], $this->options['encode_emails']);
	}

	/**
	 * RSS Filter
	 * @param string $content
	 * @return string
	 */
	function filter_rss_callback($content) {
		return preg_replace($this->regexp_patterns, $this->options['protection_text'], $content);
	}

	/**
	 * admin_menu action
	 */
	function admin_menu() {
		if (function_exists('add_options_page') AND current_user_can('manage_options')) {
			// add options page
			$page = add_options_page('Email Encoder Bundle', 'Email Encoder Bundle',
								'manage_options', __FILE__, array($this, 'options_page'));
		}
	}

	/**
	 * admin_init action
	 */
	function admin_init() {
		// register settings
		register_setting($this->domain, $this->options_name);

		// actions
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
	}

	/**
	 * admin_enqueue_scripts action
	 * @param string $hook_suffix
	 */
	function admin_enqueue_scripts($hook_suffix) {
		if($hook_suffix == 'settings_page_email-encoder-bundle/email-encoder-bundle') {
			// set dashboard postbox
			wp_enqueue_script('dashboard');
			// set dashboard style for wp < 3.2.0
			if (isset($wp_version ) AND version_compare( preg_replace('/-.*$/', '', $wp_version ), '3.2.0', '<')) {
				wp_admin_css('dashboard');
			}

			// add style and script for ajax encoder
			wp_enqueue_script('email_encoder', plugins_url('js/email-encoder-bundle.js', __FILE__ ), array('jquery'), $this->version);
			wp_enqueue_script('email_encoder_admin', plugins_url('js/email-encoder-bundle-admin.js', __FILE__ ), array('jquery'), $this->version);
		}
	}

	/**
	 * Deactivation plugin method
	 */
	function deactivation() {
		delete_option($this->options_name);
		unregister_setting($this->domain, $this->options_name);
	}

	/**
	 * Admin options page
	 */
	function options_page() {
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-custom" style="background:url( <?php echo plugins_url('images/icon-email-encoder-bundle.png', __FILE__ ) ?> ) no-repeat 50% 50%"><br></div>
		<h2>Email Encoder Bundle</h2>

			<script type="text/javascript">
				var methodInfo = <?php echo json_encode($this->methods) ?>;
			</script>
		<div class="postbox-container metabox-holder meta-box-sortables" style="width: 68%">
		<div style="margin:0 2%;">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle') ?>"><br/></div>
				<h3 class="hndle"><?php _e('General Settings') ?></h3>
				<div class="inside">
				<form method="post" action="options.php">
				<?php
					settings_fields($this->domain);
					$this->set_options();
					$options = $this->options;
				?>
					<?php if (is_plugin_active('wp-mailto-links/wp-mailto-links.php') ): ?>
						<p class="description"><?php _e('Warning: "WP Mailto Links"-plugin is also activated, which could cause conflicts.', $this->domain ) ?></p>
					<?php endif; ?>
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e('Choose encoding method', $this->domain ) ?></th>
							<td><label><select id="<?php echo $this->options_name ?>[method]" name="<?php echo $this->options_name ?>[method]" class="method-info-select postform">
								<?php foreach ($this->methods AS $method => $info ): ?>
									<option value="<?php echo $method ?>" <?php if ($this->method == $method ) echo 'selected="selected"' ?>><?php echo $info['name']; if ($method == 'lim_email_ascii'){ echo ' (recommended)'; } ?></option>
								<?php endforeach; ?>
									<option value="random" <?php if ($this->method == 'random') echo 'selected="selected"' ?>><?php echo __('Random', $this->domain ) ?></option>
								</select>
								<span class="description"></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e('Automatically encode emails', $this->domain ) ?></th>
							<td>
								<label><input type="checkbox" id="encode_mailtos" name="<?php echo $this->options_name ?>[encode_mailtos]" value="1" <?php checked('1', (int) $options['encode_mailtos']); ?> />
									<span><?php _e('Encode mailto-links', $this->domain ) ?></span>
								</label>
							<br/><label><input type="checkbox" id="encode_emails" name="<?php echo $this->options_name ?>[encode_emails]" value="1" <?php checked('1', (int) $options['encode_emails']); ?> />
									<span><?php _e('Replace plain emailaddresses to encoded mailto-links', $this->domain ) ?></span> <span class="description"><?php _e('(NOT recommended)', $this->domain ) ?></span>
								</label>
							<br/>
								<label>
									<span><?php _e('Skip posts with ID:', $this->domain ) ?></span>
									<input type="text" id="<?php echo $this->options_name ?>[skip_posts]" name="<?php echo $this->options_name ?>[skip_posts]" value="<?php echo $options['skip_posts']; ?>" />
									<span class="description"><?php _e('(comma seperated, f.e.: 2, 7, 13, 32)', $this->domain ) ?></span>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php _e('Set class for mailto-links', $this->domain ) ?></th>
							<td><label><input type="text" id="<?php echo $this->options_name ?>[class_name]" name="<?php echo $this->options_name ?>[class_name]" value="<?php echo $options['class_name']; ?>" />
								<span class="description"><?php _e('Set class-attribute for encoded mailto links <em>(optional)</em>', $this->domain ) ?></span></label></td>
						</tr>
						<tr>
							<th><?php _e('Options has effect on', $this->domain ) ?></th>
							<td><label><input type="checkbox" name="<?php echo $this->options_name ?>[filter_posts]" value="1" <?php checked('1', (int) $options['filter_posts']); ?> />
										<span><?php _e('All posts', $this->domain ) ?></span>
									</label>
								<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[filter_comments]" name="<?php echo $this->options_name ?>[filter_comments]" value="1" <?php checked('1', (int) $options['filter_comments']); ?> />
									<span><?php _e('All comments', $this->domain ) ?></span></label>
								<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[filter_widgets]" name="<?php echo $this->options_name ?>[filter_widgets]" value="1" <?php checked('1', (int) $options['filter_widgets']); ?> />
									<span><?php if ($this->options['widget_logic_filter']) { _e('All widgets (uses the <code>widget_content</code> filter of the Widget Logic plugin)', $this->domain); } else { _e('All text widgets', $this->domain); } ?></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e('Protect RSS feed', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="filter_rss" name="<?php echo $this->options_name ?>[filter_rss]" value="1" <?php checked('1', (int) $options['filter_rss']); ?> />
									<span><?php _e('Replace emails in RSS feed by ', $this->domain ) ?></span></label>
								<label><input type="text" id="protection_text" name="<?php echo $this->options_name ?>[protection_text]" value="<?php echo $options['protection_text']; ?>" />
							</td>
						</tr>
						</table>
					</fieldset>

					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e('"Email Encoder Form" settings', $this->domain ) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[powered_by]" name="<?php echo $this->options_name ?>[powered_by]" value="1" <?php checked('1', (int) $options['powered_by']); ?> /> <span><?php _e('Show the "powered by"-link on bottom of the encode form', $this->domain ) ?></span></label></td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" value="<?php _e('Save Changes') ?>" />
					</p>
				</form>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle') ?>"><br/></div>
				<h3 class="hndle"><?php _e('Email Encoder Form', $this->domain ) ?></h3>
				<div class="inside">
					<?php echo $this->get_encoder_form(); ?>
				</div>
			</div>
		</div>
		</div>

		<div class="postbox-container side metabox-holder meta-box-sortables" style="width:28%;">
		<div style="margin:0 2%;">
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle') ?>"><br/></div>
				<h3 class="hndle"><?php _e('About') ?>...</h3>
				<div class="inside">
					<h4><img src="<?php echo plugins_url('images/icon-email-encoder-bundle.png', __FILE__ ) ?>" width="16" height="16" /> Email Encoder Bundle (v<?php echo $this->version ?>)</h4>
					<p><?php _e('Protect email addresses on your site from spambots and being used for spamming by using one of the encoding methods.', $this->domain ) ?></p>
					<ul>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/faq/" target="_blank"><?php _e('Get Started - FAQ', $this->domain ) ?></a></li>
						<li><a href="http://www.freelancephp.net/contact/" target="_blank"><?php _e('Questions or suggestions?', $this->domain ) ?></a></li>
						<li><?php _e('If you like this plugin please send your rating at WordPress.org.') ?></li>
						<li><a href="http://wordpress.org/extend/plugins/email-encoder-bundle/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle') ?>"><br/></div>
				<h3 class="hndle"><?php _e('Other Plugins', $this->domain ) ?></h3>
				<div class="inside">
					<h4><img src="<?php echo plugins_url('images/icon-wp-external-links.png', __FILE__ ) ?>" width="16" height="16" /> WP External Links</h4>
					<p><?php _e('Manage external links on your site: open in new window/tab, set icon, add "external", add "nofollow" and more.', $this->domain ) ?></p>
					<ul>
						<?php if (is_plugin_active('wp-external-links/wp-external-links.php') ): ?>
							<li><?php _e('This plugin is already activated.', $this->domain ) ?> <a href="<?php echo get_bloginfo('url') ?>/wp-admin/options-general.php?page=wp-external-links/wp-external-links.php"><?php _e('Settings') ?></a></li>
						<?php elseif( file_exists( WP_PLUGIN_DIR . '/wp-external-links/wp-external-links.php') ): ?>
							<li><a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e('Activate this plugin.', $this->domain ) ?></a></li>
						<?php else: ?>
							<li><a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+External+Links+freelancephp&plugin-search-input=Search+Plugins"><?php _e('Get this plugin now', $this->domain ) ?></a></li>
						<?php endif; ?>
						<li><a href="http://wordpress.org/extend/plugins/wp-external-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-external-links-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>

					<h4><img src="<?php echo plugins_url('images/icon-wp-mailto-links.png', __FILE__ ) ?>" width="16" height="16" /> WP Mailto Links</h4>
					<p><?php _e('Manage mailto links on your site and protect emails from spambots, set mail icon and more.', $this->domain ) ?></p>
					<ul>
						<?php if (is_plugin_active('wp-mailto-links/wp-mailto-links.php') ): ?>
							<li><?php _e('This plugin is already activated.', $this->domain ) ?> <a href="<?php echo get_bloginfo('url') ?>/wp-admin/options-general.php?page=wp-mailto-links/wp-mailto-links.php"><?php _e('Settings') ?></a></li>
						<?php elseif( file_exists( WP_PLUGIN_DIR . '/wp-mailto-links/wp-mailto-links.php') ): ?>
							<li><a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e('Activate this plugin.', $this->domain ) ?></a></li>
						<?php else: ?>
							<li><a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+Mailto+Links+freelancephp&plugin-search-input=Search+Plugins"><?php _e('Get this plugin now', $this->domain ) ?></a></li>
						<?php endif; ?>
						<li><a href="http://wordpress.org/extend/plugins/wp-mailto-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-mailto-links-plugin/" target="_blank">FreelancePHP.net</a></li>
					</ul>
				</div>
			</div>
		</div>
		</div>
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
							<th><label for="email"><?php _e('Email address', $this->domain ) ?></label></th>
							<td><input type="text" class="regular-text" id="email" name="email" /></td>
						</tr>
						<tr>
							<th><label for="display"><?php _e('Display', $this->domain ) ?></label></th>
							<td><input type="text" class="regular-text" id="display" name="display" /></td>
						</tr>
						<tr>
							<th><?php _e('Example', $this->domain ) ?></th>
							<td><span id="example"></span></td>
						</tr>
						<tr>
							<th><label for="encode_method"><?php _e('Encode method', $this->domain ) ?></label></th>
							<td><select id="encode_method" name="encode_method" class="postform">
								<?php foreach ($this->methods AS $method => $info ): ?>
									<option value="<?php echo $method ?>" <?php if ($this->method == $method ) echo 'selected="selected"' ?>><?php echo $info['name'] ?></option>
								<?php endforeach; ?>
									<option value="random" <?php if ($this->method == 'random') echo 'selected="selected"' ?>><?php _e('Random', $this->domain ) ?></option>
								</select>
								<input type="button" id="ajax_encode" value="<?php _e('Encode', $this->domain ) ?> &gt;&gt;" />
							</td>
						</tr>
					</tr>
					</table>
				</div>
				<div class="output nodis">
					<table>
					<tr>
						<tr>
							<th><label for="encoded_output"><?php _e('Code', $this->domain ) ?></label></th>
							<td><textarea class="large-text node" id="encoded_output" name="encoded_output"></textarea></td>
						</tr>
					</tr>
					</table>
				</div>
			<?php if ($this->options['powered_by'] ): ?>
				<p class="powered-by"><?php _e('Powered by', $this->domain ) ?> <a rel="external" href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/">Email Encoder Bundle</a></p>
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
	 * Set options from save values or defaults
	 */
	function set_options() {
		// set options
		$saved_options = get_option($this->options_name);

		// backwards compatible (old values)
		if (empty($saved_options )) {
			$saved_options = get_option($this->domain . 'options');
		}

		if (empty($saved_options)) {
			foreach ($this->options AS $key => $option) {
				$saved_options[$key] = $this->options[$key];
			}
		}

		// upgrade to 0.40
		if (! isset($saved_options['class_name'])) {
			// set default
			$saved_options['class_name'] = $this->options['class_name'];
			$saved_options['filter_posts'] = $this->options['filter_posts'];
		}

		// upgrade to 0.50
		if (!isset($saved_options['protection_text'])) {
			// set default
			$saved_options['protection_text'] = $this->options['protection_text'];
			$saved_options['skip_posts'] = $this->options['skip_posts'];
		}

		// set all options
		if (!empty($saved_options)) {
			foreach ($this->options AS $key => $option) {
				$this->options[$key] = (empty($saved_options[$key])) ? '' : $saved_options[$key];
			}
		}

		// set encode method
		$this->method = $this->get_method($this->options['method']);

		// set widget_content filter of Widget Logic plugin
		$widget_logic_opts = get_option('widget_logic');
		if (is_array($widget_logic_opts ) AND key_exists('widget_logic-options-filter', $widget_logic_opts )) {
			$this->options['widget_logic_filter'] = ($widget_logic_opts['widget_logic-options-filter'] == 'checked') ? 1 : 0;
		}
	}

	/**
	 * Filter content for encoding
	 * @param string $content
	 * @param boolean $enc_tags Optional, default TRUE
	 * @param boolean $enc_mailtos  Optional, default TRUE
	 * @param boolean $enc_plain_emails Optional, default TRUE
	 * @return string
	 */
	function filter($content, $enc_tags = TRUE, $enc_mailtos = TRUE, $enc_plain_emails = TRUE) {
		// encode mailto links
		if ($enc_mailtos) {
			$content = preg_replace_callback($this->regexp_patterns['mailto'], array($this, 'callback_encode_email'), $content);
		}

		// replace plain emails
		if ($enc_plain_emails) {
			$content = preg_replace_callback($this->regexp_patterns['email'], array($this, 'callback_encode_email'), $content);
		}

		return $content;
	}

	/**
	 * Callback for encoding email
	 * @param array $match
	 * @return string
	 */
	function callback_encode_email($match) {
		if (count($match ) == 2) {
			return $this->encode_email($match[1]);
		}

		return $this->encode_email($match[1], $match[2]);
	}

	/**
	 * Get method name
	 * @param string $method
	 * @param string $defaultMethod Optional, default 'enc_html'
	 * @return string
	 */
	function get_method($method, $defaultMethod = 'enc_html') {
		$method = strtolower($method);

		if ('random' == $method) {
			// set a random method
			$method = array_rand($this->methods);
		} else {
			if (!method_exists($this, $method)) {
				$method = $defaultMethod; // set default method
			}
		}

		return $method;
	}

	/**
	 * Encode the given email into an encoded HTML link
	 * @param string $content
	 * @param string $method Optional, else the default setted method will; be used
	 * @return string
	 */
	function encode($content, $method = NULL) {
		// get encode method
		$method = $this->get_method($method, $this->method);

		if ($this->logged_in) {
			$content = '<div class="' . $this->domain . '">' . $content . '</div>';
		}

		// get encoded email code
		return $this->{$method}($content);
	}

	/**
	 * Encode the given email into an encoded HTML link
	 * @param string $email
	 * @param string $display Optional, if not set display will be the email
	 * @param string $method Optional, else the default setted method will; be used
	 * @param string $extra_attrs Optional
	 * @return string
	 */
	function encode_email($email, $display = NULL, $method = NULL, $extra_attrs = '') {
		// decode entities
		$email = html_entity_decode($email);

		// set email as display
		if ($display === NULL) {
			$display = $email;
		} else {
			$display = html_entity_decode($display);
		}

		// get encode method
		$method = $this->get_method($method, $this->method);

		if ($method === 'enc_html') {
			$email = $this->enc_html($email);
			$display = $this->enc_html($display);
		}

		$class = $this->options['class_name'];
		$extra_attrs = ' ' . trim($extra_attrs) . ' title="' . $display . '"';

		$html = '<a class="'. $class .'" href="mailto:' . $email . '"'. $extra_attrs . '>' . $display . '</a>';

		// get encoded email code
		return ($method === 'enc_html') ? $html : $this->encode($html, $method);
	}

	/**
	 * ASCII method
	 * Based on function from Tyler Akins (http://rumkin.com/tools/mailto_encoder/)
	 *
	 * @param string $value
	 * @return string
	 */
	function enc_ascii($value) {
		$mail_link = $value;

		$mail_letters = '';

		for ($i = 0; $i < strlen($mail_link); $i ++) {
			$l = substr($mail_link, $i, 1);

			if (strpos($mail_letters, $l) === false) {
				$p = rand(0, strlen($mail_letters));
				$mail_letters = substr($mail_letters, 0, $p) .
				$l . substr($mail_letters, $p, strlen($mail_letters));
			}
		}

		$mail_letters_enc = str_replace("\\", "\\\\", $mail_letters);
		$mail_letters_enc = str_replace("\"", "\\\"", $mail_letters_enc);

		$mail_indices = '';
		for ($i = 0; $i < strlen($mail_link); $i ++) {
			$index = strpos($mail_letters, substr($mail_link, $i, 1));
			$index += 48;
			$mail_indices .= chr($index);
		}

		$mail_indices = str_replace("\\", "\\\\", $mail_indices);
		$mail_indices = str_replace("\"", "\\\"", $mail_indices);

		return '<script type="text/javascript">/*<![CDATA[*/'
				. '(function(){'
				. 'var ML="'. $mail_letters_enc .'", MI="'. $mail_indices .'",  OT="";'
				. 'for(var j=0;j<MI.length;j++){'
				. 'OT+=ML.charAt(MI.charCodeAt(j)-48);'
				. '}document.write(OT);'
				. '}());'
				. '/*]]>*/'
				. '</script><noscript>'
				. $this->options['protection_text']
				. '</noscript>';
	}

	/**
	 * Escape method
	 * Taken from the plugin "Email Spam Protection" by Adam Hunter (http://blueberryware.net/2008/09/14/email-spam-protection/)
	 *
	 * @param string $value
	 * @return string
	 */
	function enc_escape($value) {
		$string = 'document.write(\'' . $value . '\')';

		/* break string into array of characters, we can't use string_split because its php5 only :( */
		$split = preg_split('||', $string);
		$out =  '<script type="text/javascript">/*<![CDATA[*/ ' . "eval(unescape('";

		foreach ($split as $c) {
			/* preg split will return empty first and last characters, check for them and ignore */
			if (!empty($c)) {
				$out .= '%' . dechex(ord($c));
			}
		}

		$out .= "'))" . '/*]]>*/</script><noscript>'
			. $this->options['protection_text']
			. '</noscript>';

		return $out;
	}

	/**
	 * Convert randomly chars to htmlentities
	 * This method is partly taken from WordPress
	 * @link http://codex.wordpress.org/Function_Reference/antispambot
	 *
	 * @param string $value
	 * @return string
	 */
	function enc_html($value) {
		// check for built-in WP function
		if (function_exists('antispambot')) {
			$enc_value = antispambot($value);
		} else {
			$enc_value = '';
			srand((float) microtime() * 1000000);

			for ($i = 0; $i < strlen($value); $i = $i + 1) {
				$j = floor(rand( 0, 1 ));

				if ($j == 0) {
					$enc_value .= '&#' . ord(substr($value, $i, 1)) . ';';
				} elseif ($j == 1) {
					$enc_value .= substr($value, $i, 1);
				}
			}
		}

		$enc_value = str_replace('@', '&#64;', $enc_value);

		return $enc_value;
	}

} // end class WP_Email_Encoder_Bundle



/**
 * Create instance
 */
$WP_Email_Encoder_Bundle = new WP_Email_Encoder_Bundle;


/**
 * Ajax Encoding request
 */
if (!empty($_GET['ajaxEncodeEmail'])):
	// input vars
	$method = $_GET['method'];
	$email = $_GET['email'];
	$display = (empty($_GET['display'])) ? $email : $_GET['display'];

	echo $WP_Email_Encoder_Bundle->encode_email($email, $display, $method);
	exit;
endif;

/**
 * Template function for encoding email
 * @global WP_Email_Encoder $WP_Email_Encoder_Bundle
 * @param string $email
 * @param string $display  if non given will be same as email
 * @param string $method Optional, else the default setted method will; be used
 * @param string $extra_attrs  Optional
 * @return string
 */
if (!function_exists('encode_email')):
	function encode_email($email, $display = NULL, $method = NULL, $extra_attrs = '') {
		global $WP_Email_Encoder_Bundle;
		return $WP_Email_Encoder_Bundle->encode_email($email, $display, $method, $extra_attrs);
	}
endif;

/**
 * Template function for encoding content
 * @global WP_Email_Encoder $WP_Email_Encoder_Bundle
 * @param string $content
 * @param string $method Optional, default NULL
 * @return string
 */
if (!function_exists('encode_content')):
	function encode_content($content, $method = NULL) {
		global $WP_Email_Encoder_Bundle;
		return $WP_Email_Encoder_Bundle->encode($content, $method);
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
if (!function_exists('encode_email_filter')):
	function encode_email_filter($content, $enc_tags = TRUE, $enc_mailtos = TRUE, $enc_plain_emails = TRUE ) {
		global $WP_Email_Encoder_Bundle;
		return $WP_Email_Encoder_Bundle->filter($content, $enc_tags, $enc_mailtos, $enc_plain_emails);
	}
endif;

/*?> // ommit closing tag, to prevent unwanted whitespace at the end of the parts generated by the included files */