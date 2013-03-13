<?php
/*
Plugin Name: Email Encoder Bundle
Plugin URI: http://www.freelancephp.net/email-encoder-php-class-wp-plugin/
Description: Protect email addresses on your site and hide them from spambots by using an encoding method. Easy to use, flexible .
Author: Victor Villaverde Laan
Version: 0.80
Author URI: http://www.freelancephp.net
License: Dual licensed under the MIT and GPL licenses
*/

/**
 * Class WP_Email_Encoder_Bundle_Admin
 * @package WP_Email_Encoder_Bundle
 * @category WordPress Plugins
 */
if (!class_exists('WP_Email_Encoder_Bundle_Admin')):

class WP_Email_Encoder_Bundle_Admin {

	/**
	 * Current version
	 * @var string
	 */
	var $version = '0.80';

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
	 * @var boolead
	 */
	var $is_admin_user = FALSE;

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
	 * @var string
	 */
	var $method = 'enc_ascii';

	/**
	 * @var array
	 */
	var $methods = array();

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

			if ('random' == $method) {
				$method = $this->get_method($method, $defaultMethod);
			}
		} else {
			if (!method_exists($this, $method)) {
				$method = $defaultMethod; // set default method
			}
		}

		return $method;
	}

	/**
	 * Deactivation plugin method
	 */
	function deactivation() {
		delete_option($this->options_name);
		unregister_setting($this->domain, $this->options_name);
	}

	/**
	 * wp action
	 */
	function wp() {
		// check admin
		$this->is_admin_user = current_user_can('manage_options');
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
		global $wp_version;

		if ($hook_suffix == 'settings_page_email-encoder-bundle/email-encoder-bundle' || $hook_suffix == 'toplevel_page_email-encoder-bundle/email-encoder-bundle') {
			// set dashboard postbox
			wp_enqueue_script('dashboard');

			// set dashboard style for wp < 3.2.0
			if (version_compare(preg_replace('/-.*$/', '', $wp_version), '3.2.0', '<')) {
				wp_admin_css('dashboard');
			}

			// add style and script for ajax encoder
			wp_enqueue_script('email_encoder', plugins_url('js/email-encoder-bundle.js', __FILE__), array('jquery'), $this->version);
			wp_enqueue_script('email_encoder_admin', plugins_url('js/email-encoder-bundle-admin.js', __FILE__), array('jquery'), $this->version);
		}
	}

	/**
	 * admin_menu action
	 */
	function admin_menu() {
		if ($this->is_admin_user) {
			return;
		}

		// add page and menu item
		if ($this->options['own_admin_menu']) {
		// create main menu item
			$page_hook = add_menu_page(__('Email Encoder Bundle', $this->domain), __('Email Encoder Bundle', $this->domain),
								'manage_options', __FILE__, array($this, 'show_options_page'),
								plugins_url('images/icon-email-encoder-bundle-16.png', __FILE__));
		} else {
		// create submenu item under "Settings"
			$page_hook = add_options_page(__('Email Encoder Bundle', $this->domain), __('Email Encoder Bundle', $this->domain),
								'manage_options', __FILE__, array($this, 'show_options_page'));
		}

		// load plugin page
		add_action('load-' . $page_hook, array($this, 'load_options_page'));
	}

	/* -------------------------------------------------------------------------
	 *  Admin Options Page
	 * ------------------------------------------------------------------------*/

	/**
	 * Load admin options page
	 */
	function load_options_page() {
		// add help tabs
		$this->add_help_tabs();

		// screen settings
		if (function_exists('add_screen_option')) {
			add_screen_option('layout_columns', array(
				'max' => 2,
				'default' => 2
			));
		}

		// add meta boxes
		add_meta_box('general_settings', __('General Settings'), array($this, 'show_meta_box_content'), null, 'normal', 'core', array('general_settings'));
		add_meta_box('admin_settings', __('Admin Settings'), array($this, 'show_meta_box_content'), null, 'normal', 'core', array('admin_settings'));
		add_meta_box('encode_form', __('Email Encoder Form'), array($this, 'show_meta_box_content'), null, 'normal', 'core', array('encode_form'));
		add_meta_box('other_plugins', __('Other Plugins'), array($this, 'show_meta_box_content'), null, 'side', 'core', array('other_plugins'));
	}

	/**
	 * Show admin options page
	 */
	function show_options_page() {
		$this->set_options();
?>
		<div class="wrap">
			<div class="icon32" id="icon-options-custom" style="background:url(<?php echo plugins_url('images/icon-email-encoder-bundle.png', __FILE__) ?>) no-repeat 50% 50%"><br></div>
			<h2><?php echo get_admin_page_title() ?> - <em><small><?php _e('Protecting Email Addresses', $this->domain) ?></small></em></h2>

			<?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true'): ?>
			<div class="updated settings-error" id="setting-error-settings_updated">
				<p><strong><?php _e('Settings saved.' ) ?></strong></p>
			</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields($this->domain); ?>

				<input type="hidden" name="<?php echo $this->domain ?>_nonce" value="<?php echo wp_create_nonce($this->domain) ?>" />
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
						<!--<div id="post-body-content"></div>-->

						<div id="postbox-container-1" class="postbox-container">
							<?php do_meta_boxes('', 'side', ''); ?>
						</div>

						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes('', 'normal', ''); ?>
							<?php do_meta_boxes('', 'advanced', ''); ?>
						</div>
					</div> <!-- #post-body -->
				</div> <!-- #poststuff -->
			</form>
			<script type="text/javascript">
				var methodInfo = <?php echo json_encode($this->methods) ?>;
			</script>
		</div>
<?php
	}

	/**
	 * Show content of metabox (callback)
	 * @param array $post
	 * @param array $meta_box
	 */
	function show_meta_box_content($post, $meta_box) {
		$key = $meta_box['args'][0];
		$options = $this->options;

		if ($key === 'general_settings') {
?>
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
							<br/><span class="description"><?php _e('Notice: shortcodes will still work on these posts.', $this->domain) ?></span>
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
			<br class="clear" />
<?php
		} else if ($key === 'admin_settings') {
?>
			<fieldset class="options">
				<table class="form-table">
				<tr>
					<th><?php _e('Check encoded content', $this->domain) ?></th>
					<td><label><input type="checkbox" id="<?php echo $this->options_name ?>[show_encoded_check]" name="<?php echo $this->options_name ?>[show_encoded_check]" value="1" <?php checked('1', (int) $options['show_encoded_check']); ?> /> <span><?php _e('Show "successfully encoded" text for all encoded content, only when logged in as admin user', $this->domain) ?></span> <br /><span class="description">(this way you can check if emails are really encoded on your site)</span></label></td>
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
			<br class="clear" />
<?php
		} else if ($key === 'encode_form') {
			echo $this->get_encoder_form();
		} else if ($key === 'other_plugins') {
?>
			<h4><img src="<?php echo plugins_url('images/icon-wp-external-links.png', __FILE__) ?>" width="16" height="16" /> WP External Links -
				<?php if (is_plugin_active('wp-external-links/wp-external-links.php')): ?>
					<a href="<?php echo get_bloginfo('url') ?>/wp-admin/options-general.php?page=wp-external-links/wp-external-links.php"><?php _e('Settings') ?></a>
				<?php elseif( file_exists( WP_PLUGIN_DIR . '/wp-external-links/wp-external-links.php')): ?>
					<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e('Activate', $this->domain) ?></a>
				<?php else: ?>
					<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+External+Links+freelancephp&plugin-search-input=Search+Plugins"><?php _e('Get this plugin', $this->domain) ?></a>
				<?php endif; ?>
			</h4>
			<p><?php _e('Manage external links on your site: open in new window/tab, set icon, add "external", add "nofollow" and more.', $this->domain) ?>
				<br /><a href="http://wordpress.org/extend/plugins/wp-external-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-external-links-plugin/" target="_blank">FreelancePHP.net</a>
			</p>

			<h4><img src="<?php echo plugins_url('images/icon-wp-mailto-links.png', __FILE__) ?>" width="16" height="16" /> WP Mailto Links -
				<?php if (is_plugin_active('wp-mailto-links/wp-mailto-links.php')): ?>
					<a href="<?php echo get_bloginfo('url') ?>/wp-admin/options-general.php?page=wp-mailto-links/wp-mailto-links.php"><?php _e('Settings') ?></a>
				<?php elseif( file_exists( WP_PLUGIN_DIR . '/wp-mailto-links/wp-mailto-links.php')): ?>
					<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugins.php?plugin_status=inactive"><?php _e('Activate', $this->domain) ?></a>
				<?php else: ?>
					<a href="<?php echo get_bloginfo('url') ?>/wp-admin/plugin-install.php?tab=search&type=term&s=WP+Mailto+Links+freelancephp&plugin-search-input=Search+Plugins"><?php _e('Get this plugin', $this->domain) ?></a>
				<?php endif; ?>
			</h4>
			<p><?php _e('Manage mailto links on your site and protect emails from spambots, set mail icon and more.', $this->domain) ?>
				<br /><a href="http://wordpress.org/extend/plugins/wp-mailto-links/" target="_blank">WordPress.org</a> | <a href="http://www.freelancephp.net/wp-mailto-links-plugin/" target="_blank">FreelancePHP.net</a>
			</p>
<?php
		}
	}

	/* -------------------------------------------------------------------------
	 *  Help Tabs
	 * ------------------------------------------------------------------------*/

	/**
	 * Add help tabs
	 */
	function add_help_tabs() {
		if (!function_exists('get_current_screen')) {
			return;
		}

		$screen = get_current_screen();

		$screen->set_help_sidebar($this->get_help_text('sidebar'));

		$screen->add_help_tab(array(
			'id' => 'about',
			'title'	=> __('About'),
			'content' => $this->get_help_text('about'),
		));
		$screen->add_help_tab(array(
			'id' => 'shortcodes',
			'title'	=> __('Shortcodes'),
			'content' => $this->get_help_text('shortcodes'),
		));
		$screen->add_help_tab(array(
			'id' => 'templatefunctions',
			'title'	=> __('Template functions'),
			'content' => $this->get_help_text('templatefunctions'),
		));
		$screen->add_help_tab(array(
			'id' => 'hooks',
			'title'	=> __('Hooks'),
			'content' => $this->get_help_text('hooks'),
		));
	}

	/**
	 * Get text for given help tab
	 * @param string $key
	 * @return string
	 */
	function get_help_text($key) {
		if ($key === 'about') {
			$plugin_title = get_admin_page_title();
			$icon_url = plugins_url('images/icon-email-encoder-bundle.png', __FILE__);
			$content = <<<ABOUT
<p><strong><img src="{$icon_url}" width="16" height="16" /> {$plugin_title} - version {$this->version}</strong></p>
<p>Encode mailto links and (plain) email addresses and hide them from spambots. Easy to use, plugin works directly when activated. Save way to protect email addresses on your site.</p>
ABOUT;
		} else if ($key === 'shortcodes') {
			$content = <<<SHORTCODES
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
		} else if ($key === 'templatefunctions') {
			$content = <<<TEMPLATEFUNCTIONS
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
		} else if ($key === 'hooks') {
			$content = <<<HOOKS
<p>Add extra code on initializing this plugin, like extra filters for encoding.</p>
<pre>
function extra_encode_filters(\$filter_callback, \$object) {
	add_filter('some_filter', \$filter_callback);
}
add_action('init_email_encoder_bundle', 'extra_encode_filters');
</pre>
HOOKS;
		} else if ($key === 'sidebar') {
			$content = <<<SIDEBAR
<p>See <a href="http://wordpress.org/extend/plugins/email-encoder-bundle/faq/" target="_blank">FAQ</a> at WordPress.org</p>
<p>Send your <a href="http://www.freelancephp.net/contact/" target="_blank">question</a></p>
<p><strong>Please <a href="http://wordpress.org/extend/plugins/email-encoder-bundle/" target="_blank">rate this plugin</a> and vote if the plugin works.</strong></p>
SIDEBAR;
		}

		return ((empty($content)) ? '' : __($content, $this->domain));
	}

	/* -------------------------------------------------------------------------
	 * Encoder Form
	 * -------------------------------------------------------------------------/

	/**
	 * Get the encoder form (to use as a demo, like on the options page)
	 * @return string
	 */
	function get_encoder_form() {
		$lang_email = __('Email Address:', $this->domain);
		$lang_display = __('Display Text:', $this->domain);
		$lang_mailto = __('Mailto Link:', $this->domain);
		$lang_method = __('Encoding Method:', $this->domain);
		$lang_create = __('Create Protected Mail Link &gt;&gt;', $this->domain);
		$lang_output = __('Protected Mail Link (code):', $this->domain);

		$method_options = '';
		foreach ($this->methods as $method => $info) {
			$method_options .= '<option value="' . $method . '"' . (($this->method == $method) ? ' selected="selected"' : '') . '>' . $info['name'] . '</option>';
		}

		$powered_by = '';
		if ($this->options['powered_by']) {
			$powered_by .= '<p class="powered-by">' . __('Powered by', $this->domain) . ' <a rel="external" href="http://www.freelancephp.net/email-encoder-php-class-wp-plugin/">Email Encoder Bundle</a></p>';
		}

		return <<<FORM
<div class="email-encoder-form">
	<form>
		<fieldset>
			<div class="input">
				<table>
				<tbody>
					<tr>
						<th><label for="email">{$lang_email}</label></th>
						<td><input type="text" class="regular-text" id="email" name="email" /></td>
					</tr>
					<tr>
						<th><label for="display">{$lang_display}</label></th>
						<td><input type="text" class="regular-text" id="display" name="display" /></td>
					</tr>
					<tr>
						<th>{$lang_mailto}</th>
						<td><span id="example"></span></td>
					</tr>
					<tr>
						<th><label for="encode_method">{$lang_method}</label></th>
						<td><select id="encode_method" name="encode_method" class="postform">
								{$method_options}
							</select>
							<input type="button" id="ajax_encode" value="{$lang_create}" />
						</td>
					</tr>
				</tbody>
				</table>
			</div>
			<div class="output nodis">
				<table>
				<tbody>
					<tr>
						<th><label for="encoded_output">{$lang_output}</label></th>
						<td><textarea class="large-text node" id="encoded_output" name="encoded_output" cols="50" rows="4"></textarea></td>
					</tr>
				</tbody>
				</table>
			</div>
			{$powered_by}
		</fieldset>
	</form>
</div>
FORM;
	}

} // end class WP_Email_Encoder_Bundle_Admin

endif;


/**
 * Class WP_Email_Encoder_Bundle
 * @package WP_Email_Encoder_Bundle
 * @category WordPress Plugins
 */
if (!class_exists('WP_Email_Encoder_Bundle')):

class WP_Email_Encoder_Bundle extends WP_Email_Encoder_Bundle_Admin {

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
		parent::__construct();
	}

	/**
	 * wp action
	 */
	function wp() {
		parent::wp();

		if (is_feed()) {
		// rss feed
			if ($this->options['filter_rss']) {
				$rss_filters = array('the_title', 'the_content', 'the_excerpt', 'the_title_rss', 'the_content_rss', 'the_excerpt_rss',
									'comment_text_rss', 'comment_author_rss', 'the_category_rss', 'the_content_feed', 'author_feed_link', 'feed_link');

				foreach($rss_filters as $filter) {
					add_filter($filter, array($this, 'callback_filter_rss'), 100);
				}
			}
		} else {
		// site
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
				add_filter('widget_content', 'do_shortcode', 100); // widget_content id filter of Widget Logic plugin
			}

			foreach($filters as $filter) {
				add_filter($filter, array($this, 'callback_filter'), 100);
			}
		}

		// shortcodes
		add_shortcode('email_encoder_form', array($this, 'shortcode_email_encoder_form'));
		add_shortcode('encode_email', array($this, 'shortcode_encode_email'));
		add_shortcode('encode_content', array($this, 'shortcode_encode_content'));

		// actions
		add_action('wp_head', array($this, 'wp_head'));

		// hook
		do_action('init_email_encoder_bundle', array($this, 'callback_filter'), $this);
	}

	/**
	 * WP head
	 */
	function wp_head() {
			// add styling for encoding check message + icon
			if ($this->is_admin_user && $this->options['show_encoded_check']) {
		echo <<<CSS
<style type="text/css">
	a.encoded-check { opacity:0.5; position:absolute; text-decoration:none !important; font:10px Arial !important; margin-top:-3px; color:#629632; font-weight:bold; }
	a.encoded-check:hover { opacity:1; cursor:help; }
	a.encoded-check img { width:10px; height:10px; }
</style>
CSS;
			}
	}

	/* -------------------------------------------------------------------------
	 *  Filter Callbacks
	 * -------------------------------------------------------------------------/

	/**
	 * WP filter callback
	 * @param string $content
	 * @return string
	 */
	function callback_filter($content) {
		global $post;

		if (isset($post) && in_array($post->ID, $this->skip_posts)) {
			return $content;
		}

		return $this->encode_email_filter($content, TRUE, $this->options['encode_mailtos'], $this->options['encode_emails']);
	}

	/**
	 * RSS Filter callback
	 * @param string $content
	 * @return string
	 */
	function callback_filter_rss($content) {
		return preg_replace($this->regexp_patterns, $this->options['protection_text'], $content);
	}

	/**
	 * Filter content for encoding
	 * @param string $content
	 * @param boolean $enc_tags Optional, default TRUE
	 * @param boolean $enc_mailtos  Optional, default TRUE
	 * @param boolean $enc_plain_emails Optional, default TRUE
	 * @return string
	 */
	function encode_email_filter($content, $enc_tags = TRUE, $enc_mailtos = TRUE, $enc_plain_emails = TRUE) {
		// encode mailto links
		if ($enc_mailtos) {
			$content = preg_replace_callback($this->regexp_patterns['mailto'], array($this, 'callback_encode_email'), $content);
		}

		// replace plain emails
		if ($enc_plain_emails) {
			$content = preg_replace_callback($this->regexp_patterns['email'], array($this, 'callback_encode_email'), $content);
		}

		// workaround for double encoding bug when auto-protect mailto is enabled and method is enc_html
		if ($this->options['encode_mailtos'] == 1) {
			// change back to html tag
			$content = str_replace('[a-replacement]', '<a', $content);
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

	/* -------------------------------------------------------------------------
	 *  Shortcode Functions
	 * -------------------------------------------------------------------------/

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

		$encoded = $this->encode_email($email, $display, $method, $extra_attrs);

		// workaround for double encoding bug when auto-protect mailto is enabled and method is enc_html
		if ($this->options['encode_mailtos'] == 1 && $method === 'enc_html') {
			// change html tag to entity
			$encoded = str_replace('<a', '[a-replacement]', $encoded);
		}

		return $encoded;
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

	/* -------------------------------------------------------------------------
	 *  Encode Functions
	 * -------------------------------------------------------------------------/

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

		// get encoded email code
		$content = $this->{$method}($content);

		// add visual check
		if ($no_html_checked !== TRUE) {
			$content = $this->get_success_check($content);
		}

		return $content;
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
		// get encode method
		$method = $this->get_method($method, $this->method);

		// decode entities
		$email = html_entity_decode($email);

		// set email as display
		if ($display === NULL) {
			$display = $email;

			if ($method === 'enc_html') {
				$display = $this->enc_html($display);
			}
		} else {
			$display = html_entity_decode($display);
		}

		if ($method === 'enc_html') {
			$email = $this->enc_html($email);
		}

		$class = $this->options['class_name'];
		$extra_attrs = ' ' . trim($extra_attrs);
		$mailto = '<a class="'. $class .'" href="mailto:' . $email . '"'. $extra_attrs . '>' . $display . '</a>';

		if ($method === 'enc_html') {
			// add visual check
			if ($no_html_checked !== TRUE) {
				$mailto = $this->get_success_check($mailto);
			}
		} else {
			$mailto = $this->encode($mailto, $method, $no_html_checked);
		}

		// get encoded email code
		return $mailto;
	}

	/**
	 * Add html to encoded content to show check icon and text
	 * @param string $content
	 * @return string
	 */
	function get_success_check($content) {
		if (!$this->is_admin_user || !$this->options['show_encoded_check']) {
			return $content;
		}

		return $content
				. '<a href="javascript:;" class="encoded-check"'
				. ' title="' . __('Successfully Encoded (this is a check and only visible when logged in as admin)', $this->domain) . '">'
				. '<img class="encoded-check-icon" src="' . plugins_url('images/icon-email-encoder-bundle.png', __FILE__)
				. '" alt="' . __('Encoded', $this->domain) . '" />'
				. __('Successfully Encoded', $this->domain) . '</a>';
	}

	/* -------------------------------------------------------------------------
	 *  Different Encoding Methods
	 * -------------------------------------------------------------------------/

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

		return '<script type="text/javascript">'
				. '(function(){'
				. 'var ml="'. $mail_letters_enc .'",mi="'. $mail_indices .'",o="";'
				. 'for(var j=0,l=mi.length;j<l;j++){'
				. 'o+=ml.charAt(mi.charCodeAt(j)-48);'
				. '}document.write(o);'
				. '}());'
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
		$out =  '<script type="text/javascript">' . "eval(unescape('";

		foreach ($split as $c) {
			/* preg split will return empty first and last characters, check for them and ignore */
			if (!empty($c)) {
				$out .= '%' . dechex(ord($c));
			}
		}

		$out .= "'))" . '</script><noscript>'
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
			$emailNOSPAMaddy = antispambot($value);
		} else {
			$emailNOSPAMaddy = '';
			srand ((float) microtime() * 1000000);
			for ($i = 0; $i < strlen($emailaddy); $i = $i + 1) {
				$j = floor(rand(0, 1+$mailto));
				if ($j==0) {
					$emailNOSPAMaddy .= '&#'.ord(substr($emailaddy,$i,1)).';';
				} elseif ($j==1) {
					$emailNOSPAMaddy .= substr($emailaddy,$i,1);
				} elseif ($j==2) {
					$emailNOSPAMaddy .= '%'.zeroise(dechex(ord(substr($emailaddy, $i, 1))), 2);
				}
			}
			$emailNOSPAMaddy = str_replace('@','&#64;',$emailNOSPAMaddy);
		}

		$emailNOSPAMaddy = str_replace('@', '&#64;', $emailNOSPAMaddy);

		return $emailNOSPAMaddy;
	}

} // end class WP_Email_Encoder_Bundle

endif;


/*******************************************************************************
 * Create instance
 *******************************************************************************/

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


/*******************************************************************************
 * Template Functions
 *******************************************************************************/

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
		return $WP_Email_Encoder_Bundle->encode_email_filter($content, $enc_tags, $enc_mailtos, $enc_plain_emails);
	}
endif;

/*?> // ommit closing tag, to prevent unwanted whitespace at the end of the parts generated by the included files */