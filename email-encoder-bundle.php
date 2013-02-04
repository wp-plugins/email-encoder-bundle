<?php
/*
Plugin Name: Email Encoder Bundle
Plugin URI: http://www.freelancephp.net/email-encoder-php-class-wp-plugin/
Description: Protect email addresses on your site and hide them from spambots by using an encoding method. Easy to use, flexible .
Author: Victor Villaverde Laan
Version: 0.71
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
	var $version = '0.71';

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
	 * @var string
	 */
	var $page_hook = null;

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
		'show_encoded_check' => 0,
		'own_admin_menu' => 1,
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
	var $methods = array();

	/**
	 * Regexp
	 * @var array
	 */
	var $regexp_patterns = array(
		'mailto' => '/<a([^<>]*?)href=["\']mailto:(.*?)["\'](.*?)>(.*?)<\/a[\s+]*>/is',
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

		// set methods
		$this->methods = array(
			'enc_ascii' => array(
				'name' => __('JavaScript ASCII (recommended)', $this->domain),
				'description' => __('This encoding method uses javascript (<a href="http://rumkin.com/tools/mailto_encoder/" target="_blank">original source</a>). <br />Recommended, the savest method.', $this->domain),
			),
			'enc_escape' => array(
				'name' => __('JavaScript Escape', $this->domain),
				'description' => __('This encoding method uses the javascript eval() function (<a href="http://blueberryware.net/2008/09/14/email-spam-protection/" target="_blank">original source</a>). <br />Pretty save method.', $this->domain),
			),
			'enc_html' => array(
				'name' => __('Html Encode', $this->domain),
				'description' => __('This encoding method uses the antispambot() function, built-in WordPress (<a href="http://codex.wordpress.org/Function_Reference/antispambot" target="_blank">more info</a>). <br />Not recommended, especially when using the shortcode [encode_content]).', $this->domain),
			),
			'random' => array(
				'name' => __('Random', $this->domain),
				'description' => __('Pick each time a random encoding method. <br />Not recommended, especially when using the shortcode [encode_content]).', $this->domain),
			),
		);

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
		$this->logged_in = (bool) ($user_ID && current_user_can('manage_options'));

		// shortcodes
		add_shortcode('email_encoder_form', array($this, 'shortcode_email_encoder_form'));
		add_shortcode('encode_email', array($this, 'shortcode_encode_email'));
		add_shortcode('encode_content', array($this, 'shortcode_encode_content'));

		if (is_feed()) {
			// rss feed
			if ($this->options['filter_rss']) {
				$rss_filters = array('the_title', 'the_content', 'the_excerpt', 'the_title_rss', 'the_content_rss', 'the_excerpt_rss',
									'comment_text_rss', 'comment_author_rss', 'the_category_rss', 'the_content_feed', 'author_feed_link', 'feed_link');

				foreach($rss_filters as $filter) {
					add_filter($filter, array($this, 'filter_rss_callback'), 100);
				}
			}
		} else {
			// add style when logged in
			if ($this->logged_in) {
				add_action('wp_head', array($this, 'wp_head'));
			}

			$filters = array();

			// post content
			if ($this->options['filter_posts']) {
				array_push($filters, 'the_title', 'the_content', 'the_excerpt', 'get_the_excerpt');
			}

			// comments
			if ($this->options['filter_comments']) {
				array_push($filters, 'comment_text', 'comment_excerpt', 'comment_url', 'get_comment_author_url', 'get_comment_author_link', 'get_comment_author_url_link');
			}

			// widgets
			if ($this->options['filter_widgets']) {
				array_push($filters, 'widget_title', 'widget_text', 'widget_content');

				// also replace shortcodes
				add_filter('widget_text', 'do_shortcode', 100);
				add_filter('widget_content', 'do_shortcode', 100); // filter of Widget Logic plugin
			}

			foreach($filters as $filter) {
				add_filter($filter, array($this, 'filter_callback'), 100);
			}
		}

		// action hook
		do_action('init_email_encoder_bundle', array($this, 'filter_callback'), $this);
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
		if (!is_array($attrs) || !key_exists('email', $attrs)) {
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
		$method = (is_array($attrs) && key_exists('method', $attrs)) ? $attrs['method'] : NULL;

		return $this->encode($content, $method);
	}

	/**
	 * Add style/script for encoded mails when logged in
	 */
	function wp_head() {
		echo '<style type="text/css">' . "\n";
		echo 'a.encoded-check { opacity:0.5; position:absolute; text-decoration:none !important; font:10px Arial !important; margin-top:-3px; color:#629632; font-weight:bold; }' . "\n";
		echo 'a.encoded-check:hover { opacity:1; cursor:help; }' . "\n";
		echo 'a.encoded-check img { width:10px; height:10px; }' . "\n";
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
			if ($this->options['own_admin_menu']) {
				$this->page_hook = add_menu_page('Email Encoder Bundle', 'Email Encoder Bundle',
									'manage_options', __FILE__, array($this, 'options_page'),
									plugins_url('images/icon-email-encoder-bundle-16.png', __FILE__));
			} else {
				$this->page_hook = add_options_page('Email Encoder Bundle', 'Email Encoder Bundle',
									'manage_options', __FILE__, array($this, 'options_page'));
			}

			add_action('load-' . $this->page_hook, array($this, 'help_tabs'));
			add_filter('contextual_help', array($this, 'contextual_help'), 10, 3);
		}
	}

	/**
	 * Remove default contextual help text
	 * @param string $contextual_help
	 * @param integer $screen_id
	 * @param object $screen
	 * @return string
	 */
	function contextual_help($contextual_help, $screen_id, $screen) {
		if ($screen_id == $this->page_hook) {
			$contextual_help = '';
		}

		return $contextual_help;
	}

	/**
	 * Create help tabs
	 */
	function help_tabs() {
		if (!function_exists('get_current_screen')) {
			return;
		}

		$screen = get_current_screen();

		$about = <<<ABOUT
<p><strong>Email Encoder Bundle - version {$this->version}</strong></p>
<p>Encode mailto links and (plain) email addresses and hide them from spambots. Easy to use, plugin works directly when activated. Save way to protect email addresses on your site.</p>
ABOUT;
		$screen->add_help_tab(array(
			'id' => 'about',
			'title'	=> __('About'),
			'content' => __($about),
		));

		$shortcodes = <<<SHORTCODES
<p>Encode an email address:
<br/><code>[encode_email email="..." display="..."]</code> ("display" is optional)
</p>
<p>Encode some content:
<br/><code>[encode_content method="..."]...[/encode_content]</code> ("method" is optional)
</p>
<p>Puts an encoder form in your post:
<br/><code>[email_encoder_form]</code>
</p>
SHORTCODES;
		$screen->add_help_tab(array(
			'id' => 'shortcodes',
			'title'	=> __('Shortcodes'),
			'content' => __($shortcodes),
		));

		$templatefunctions = <<<TEMPLATEFUNCTIONS
<p>Encode the given email (other params are optional):
<br/><code><&#63;php echo encode_email(\$email, [\$display], [\$method], [\$extra_attrs]); &#63;></code>
</p>
<p>Encode the given content for emails to encode (other param is optional):
<br/><code><&#63;php echo encode_content(\$content, [\$method]); &#63;></code>
</p>
<p>Filter the given content for emails to encode (other params are optional):
<br/><code><&#63;php echo encode_email_filter(\$content, [\$enc_tags], [\$enc_mailtos], [\$enc_plain_emails]); &#63;></code>
</p>
TEMPLATEFUNCTIONS;
		$screen->add_help_tab(array(
			'id' => 'templatefunctions',
			'title'	=> __('Template functions'),
			'content' => __($templatefunctions),
		));

		$hooks = <<<HOOKS
<p>Add extra code on initializing this plugin, like extra filters for encoding.</p>
<pre>
function extra_encode_filters(\$filter_callback, \$object) {
	add_filter('some_filter', \$filter_callback);
}
add_action('init_email_encoder_bundle', 'extra_encode_filters');
</pre>
HOOKS;
		$screen->add_help_tab(array(
			'id' => 'hooks',
			'title'	=> __('Hooks'),
			'content' => __($hooks),
		));

		$sidebar = <<<SIDEBAR
<p>See <a href="http://wordpress.org/extend/plugins/email-encoder-bundle/faq/" target="_blank">FAQ</a> at WordPress.org</p>
<p>Send your <a href="http://www.freelancephp.net/contact/" target="_blank">question</a></p>
<p><strong>Please <a href="http://wordpress.org/extend/plugins/email-encoder-bundle/" target="_blank">rate this plugin</a> and vote if the plugin works.</strong></p>
SIDEBAR;
		$screen->set_help_sidebar(__($sidebar));
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
		if ($hook_suffix == 'settings_page_email-encoder-bundle/email-encoder-bundle' || $hook_suffix == 'toplevel_page_email-encoder-bundle/email-encoder-bundle') {
			// set dashboard postbox
			wp_enqueue_script('dashboard');
			// set dashboard style for wp < 3.2.0
			if (isset($wp_version) AND version_compare(preg_replace('/-.*$/', '', $wp_version), '3.2.0', '<')) {
				wp_admin_css('dashboard');
			}

			// add style and script for ajax encoder
			wp_enqueue_script('email_encoder', plugins_url('js/email-encoder-bundle.js', __FILE__), array('jquery'), $this->version);
			wp_enqueue_script('email_encoder_admin', plugins_url('js/email-encoder-bundle-admin.js', __FILE__), array('jquery'), $this->version);
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
		<div class="icon32" id="icon-options-custom" style="background:url(<?php echo plugins_url('images/icon-email-encoder-bundle.png', __FILE__) ?>) no-repeat 50% 50%"><br></div>
		<h2>Email Encoder Bundle - <em><small><?php _e('Protecting Email Addresses', $this->domain) ?></small></em></h2>

			<script type="text/javascript">
				var methodInfo = <?php echo json_encode($this->methods) ?>;
			</script>
		<div class="postbox-container metabox-holder meta-box-sortables" style="width: 68%">
		<div style="margin:0 2%;">
		<form method="post" action="options.php">
		<?php
			settings_fields($this->domain);
			$this->set_options();
			$options = $this->options;
		?>
			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle') ?>"><br/></div>
				<h3 class="hndle"><?php _e('General Settings') ?></h3>
				<div class="inside">
					<?php if (is_plugin_active('wp-mailto-links/wp-mailto-links.php')): ?>
						<p class="description"><?php _e('Warning: "WP Mailto Links"-plugin is also activated, which could cause conflicts.', $this->domain) ?></p>
					<?php endif; ?>
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e('Encoding Method for Protection', $this->domain) ?></th>
							<td><select id="<?php echo $this->options_name ?>[method]" name="<?php echo $this->options_name ?>[method]" class="method-info-select postform">
								<?php foreach ($this->methods AS $method => $info): ?>
									<option value="<?php echo $method ?>" <?php if ($this->method == $method) echo 'selected="selected"' ?>><?php echo $info['name']; if ($method == 'lim_email_ascii'){ echo ' (recommended)'; } ?></option>
								<?php endforeach; ?>
								</select>
								<br />
								<label><span class="description"></span></label>
							</td>
						</tr>
						<tr>
							<th><?php _e('Auto-Protect Emails', $this->domain) ?></th>
							<td>
								<label><input type="checkbox" id="encode_mailtos" name="<?php echo $this->options_name ?>[encode_mailtos]" value="1" <?php checked('1', (int) $options['encode_mailtos']); ?> />
									<span><?php _e('Protect mailto links', $this->domain) ?></span> <span class="description"><?php _e('(example: &lt;a href="info@myemail.com"&gt;My Email&lt;/a&gt;)', $this->domain) ?></span>
								</label>
							<br/><label><input type="checkbox" id="encode_emails" name="<?php echo $this->options_name ?>[encode_emails]" value="1" <?php checked('1', (int) $options['encode_emails']); ?> />
									<span><?php _e('Replace plain email addresses to protected mailto links', $this->domain) ?></span> <span class="description"><?php _e('(not recommended)', $this->domain) ?></span>
								</label>
							<br/>
							<br/>
								Apply on:
								<br/>
								<label><input type="checkbox" name="<?php echo $this->options_name ?>[filter_posts]" value="1" <?php checked('1', (int) $options['filter_posts']); ?> />
										<span><?php _e('All posts', $this->domain) ?></span>
									</label>
								<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[filter_comments]" name="<?php echo $this->options_name ?>[filter_comments]" value="1" <?php checked('1', (int) $options['filter_comments']); ?> />
									<span><?php _e('All comments', $this->domain) ?></span></label>
								<br/><label><input type="checkbox" id="<?php echo $this->options_name ?>[filter_widgets]" name="<?php echo $this->options_name ?>[filter_widgets]" value="1" <?php checked('1', (int) $options['filter_widgets']); ?> />
									<span><?php if ($this->options['widget_logic_filter']) { _e('All widgets (uses the <code>widget_content</code> filter of the Widget Logic plugin)', $this->domain); } else { _e('All text widgets', $this->domain); } ?></span></label>
							<br/>
							<br/>
								<label>
									<span><?php _e('Do <strong>not</strong> apply Auto-Protect on posts with ID:', $this->domain) ?></span>
									<br/><input type="text" id="<?php echo $this->options_name ?>[skip_posts]" name="<?php echo $this->options_name ?>[skip_posts]" value="<?php echo $options['skip_posts']; ?>" />
									<span class="description"><?php _e('(comma seperated, f.e.: 2, 7, 13, 32)', $this->domain) ?></span>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php _e('Class for Protected Links', $this->domain) ?></th>
							<td><label><input type="text" id="<?php echo $this->options_name ?>[class_name]" name="<?php echo $this->options_name ?>[class_name]" value="<?php echo $options['class_name']; ?>" />
								<span class="description"><?php _e('All protected mailto links will get these class(es) <em>(optional, else keep blank)</em>', $this->domain) ?></span></label></td>
						</tr>
						<tr>
							<th><?php _e('Protect Emails in RSS Feeds', $this->domain) ?></th>
							<td><label><input type="checkbox" id="filter_rss" name="<?php echo $this->options_name ?>[filter_rss]" value="1" <?php checked('1', (int) $options['filter_rss']); ?> />
									<span><?php _e('Replace emails in RSS feeds with the following text:', $this->domain) ?></span></label>
								<label><input type="text" id="protection_text" name="<?php echo $this->options_name ?>[protection_text]" value="<?php echo $options['protection_text']; ?>" />
							</td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" disabled="disabled" value="<?php _e('Save Changes') ?>" />
					</p>
				</div>
			</div>

			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle') ?>"><br/></div>
				<h3 class="hndle"><?php _e('Other Settings') ?></h3>
				<div class="inside">
					<fieldset class="options">
						<table class="form-table">
						<tr>
							<th><?php _e('Check encoded content', $this->domain) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[show_encoded_check]" name="<?php echo $this->options_name ?>[show_encoded_check]" value="1" <?php checked('1', (int) $options['show_encoded_check']); ?> /> <span><?php _e('Show "sucessfully encoded" text for all encoded content, only when logged in as admin user', $this->domain) ?></span> <br /><span class="description">(this way you can check if emails are really encoded on your site)</span></label></td>
						</tr>
						<tr>
							<th><?php _e('Admin menu position', $this->domain) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[own_admin_menu]" name="<?php echo $this->options_name ?>[own_admin_menu]" value="1" <?php checked('1', (int) $options['own_admin_menu']); ?> /> <span><?php _e('Show as main menu item', $this->domain) ?></span> <span class="description">(when disabled item will be shown under "General settings")</span></label></td>
						</tr>
						<tr>
							<th><?php _e('Email Encoder Form Settings', $this->domain) ?></th>
							<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[powered_by]" name="<?php echo $this->options_name ?>[powered_by]" value="1" <?php checked('1', (int) $options['powered_by']); ?> /> <span><?php _e('Show the "powered by"-link on bottom of the encoder form', $this->domain) ?></span></label></td>
						</tr>
						</table>
					</fieldset>
					<p class="submit">
						<input class="button-primary" type="submit" disabled="disabled" value="<?php _e('Save Changes') ?>" />
					</p>
				</div>
			</div>
		</form>

			<div class="postbox">
				<div class="handlediv" title="<?php _e('Click to toggle') ?>"><br/></div>
				<h3 class="hndle"><?php _e('Email Encoder Form', $this->domain) ?></h3>
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
				<h3 class="hndle"><?php _e('Other Plugins', $this->domain) ?></h3>
				<div class="inside">
					<h4><img src="<?php echo plugins_url('images/icon-wp-external-links.png', __FILE__) ?>" width="16" height="16" /> WP External Links -
						<em>
						<?php if (is_plugin_active('wp-external-links/wp-external-links.php')): ?>
							<?php _e('Activated, see', $this->domain) ?> <a href="<?php echo get_bloginfo('url') ?>/wp-admin/options-general.php?page=wp-external-links/wp-external-links.php"><?php _e('Settings') ?></a>
						<?php elseif( file_exists( WP_PLUGIN_DIR . '/wp-external-links/wp-external-links.php')): ?>
							<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e('Activate this plugin.', $this->domain) ?></a>
						<?php else: ?>
							<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+External+Links+freelancephp&plugin-search-input=Search+Plugins"><?php _e('Get this plugin now', $this->domain) ?></a>
						<?php endif; ?>
						</em>
					</h4>
					<p><?php _e('Manage external links on your site: open in new window/tab, set icon, add "external", add "nofollow" and more.', $this->domain) ?>
						<br /><a href="http://wordpress.org/extend/plugins/wp-external-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-external-links-plugin/" target="_blank">FreelancePHP.net</a>
					</p>

					<h4><img src="<?php echo plugins_url('images/icon-wp-mailto-links.png', __FILE__) ?>" width="16" height="16" /> WP Mailto Links -
						<em>
						<?php if (is_plugin_active('wp-mailto-links/wp-mailto-links.php')): ?>
							<?php _e('Activated, see', $this->domain) ?> <a href="<?php echo get_bloginfo('url') ?>/wp-admin/options-general.php?page=wp-mailto-links/wp-mailto-links.php"><?php _e('Settings') ?></a>
						<?php elseif( file_exists( WP_PLUGIN_DIR . '/wp-mailto-links/wp-mailto-links.php')): ?>
							<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e('Activate this plugin.', $this->domain) ?></a>
						<?php else: ?>
							<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+Mailto+Links+freelancephp&plugin-search-input=Search+Plugins"><?php _e('Get this plugin now', $this->domain) ?></a>
						<?php endif; ?>
						</em>
					</h4>
					<p><?php _e('Manage mailto links on your site and protect emails from spambots, set mail icon and more.', $this->domain) ?>
						<br /><a href="http://wordpress.org/extend/plugins/wp-mailto-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-mailto-links-plugin/" target="_blank">FreelancePHP.net</a>
					</p>
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
					<tbody>
						<tr>
							<th><label for="email"><?php _e('Email Address:', $this->domain) ?></label></th>
							<td><input type="text" class="regular-text" id="email" name="email" /></td>
						</tr>
						<tr>
							<th><label for="display"><?php _e('Display Text:', $this->domain) ?></label></th>
							<td><input type="text" class="regular-text" id="display" name="display" /></td>
						</tr>
						<tr>
							<th><?php _e('Mailto Link', $this->domain) ?></th>
							<td><span id="example"></span></td>
						</tr>
						<tr>
							<th><label for="encode_method"><?php _e('Encoding Method:', $this->domain) ?></label></th>
							<td><select id="encode_method" name="encode_method" class="postform">
								<?php foreach ($this->methods AS $method => $info): ?>
									<option value="<?php echo $method ?>" <?php if ($this->method == $method) echo 'selected="selected"' ?>><?php echo $info['name'] ?></option>
								<?php endforeach; ?>
								</select>
								<input type="button" id="ajax_encode" value="<?php _e('Create Protected Mail Link', $this->domain) ?> &gt;&gt;" />
							</td>
						</tr>
					</tbody>
					</table>
				</div>
				<div class="output nodis">
					<table>
					<tbody>
						<tr>
							<th><label for="encoded_output"><?php _e('Protected Mail Link (code):', $this->domain) ?></label></th>
							<td><textarea class="large-text node" id="encoded_output" name="encoded_output" cols="50" rows="4"></textarea></td>
						</tr>
					</tbody>
					</table>
				</div>
			<?php if ($this->options['powered_by']): ?>
				<p class="powered-by"><?php _e('Powered by', $this->domain) ?> <a rel="external" href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/">Email Encoder Bundle</a></p>
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
		if (empty($saved_options)) {
			$saved_options = get_option($this->domain . 'options');
		}

		// set all options
		if (!empty($saved_options)) {
			foreach ($saved_options AS $key => $value) {
				$this->options[$key] = $value;
			}
		}

		// set encode method
		$this->method = $this->get_method($this->options['method']);

		// set widget_content filter of Widget Logic plugin
		$widget_logic_opts = get_option('widget_logic');
		if (is_array($widget_logic_opts) AND key_exists('widget_logic-options-filter', $widget_logic_opts)) {
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
		if (count($match) < 3) {
			return $this->encode_email($match[1]);
		} else if (count($match) == 3) {
			return $this->encode_email($match[2]);
		}

		return $this->encode_email($match[2], $match[4], null, $match[1] . ' ' . $match[3]);
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
	 * Add html to encoded content to show check icon and text
	 * @param string $content
	 * @return string
	 */
	function get_html_checked($content) {
		if (!$this->logged_in || !$this->options['show_encoded_check']) {
			return $content;
		}

		return $content
				. '<a href="javascript:;" class="encoded-check"'
				. ' title="' . __('Successfully Encoded (this is a check and only visible when logged in as admin)', $this->domain) . '">'
				. '<img class="encoded-check-icon" src="' . plugins_url('images/icon-email-encoder-bundle.png', __FILE__)
				. '" alt="' . __('Encoded', $this->domain) . '" />'
				. __('Successfully Encoded', $this->domain) . '</a>';
	}

	/**
	 * Encode the given email into an encoded HTML link
	 * @param string $content
	 * @param string $method Optional, else the default setted method will; be used
	 * @param boolean $no_html_checked
	 * @return string
	 */
	function encode($content, $method = NULL, $no_html_checked = FALSE) {
		// get encode method
		$method = $this->get_method($method, $this->method);

		// add visual check
		if ($no_html_checked !== TRUE) {
			$content = $this->get_html_checked($content);
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
	function encode_email($email, $display = NULL, $method = NULL, $extra_attrs = '', $no_html_checked = FALSE) {
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
		$extra_attrs = ' ' . trim($extra_attrs);
		$mailto = '<a class="'. $class .'" href="mailto:' . $email . '"'. $extra_attrs . '>' . $display . '</a>';

		if ($method === 'enc_html') {
			// add visual check
			if ($no_html_checked !== TRUE) {
				$mailto = $this->get_html_checked($mailto);
			}
		} else {
			$mailto = $this->encode($mailto, $method, $no_html_checked);
		}

		// get encoded email code
		return $mailto;
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
				. 'var ML="'. $mail_letters_enc .'",MI="'. $mail_indices .'",OT="";'
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
				$j = floor(rand( 0, 1));

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

	echo $WP_Email_Encoder_Bundle->encode_email($email, $display, $method, '', TRUE);
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
	function encode_email_filter($content, $enc_tags = TRUE, $enc_mailtos = TRUE, $enc_plain_emails = TRUE) {
		global $WP_Email_Encoder_Bundle;
		return $WP_Email_Encoder_Bundle->filter($content, $enc_tags, $enc_mailtos, $enc_plain_emails);
	}
endif;

/*?> // ommit closing tag, to prevent unwanted whitespace at the end of the parts generated by the included files */