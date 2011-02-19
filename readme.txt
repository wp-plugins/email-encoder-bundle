=== Email Encoder Bundle ===
Contributors: freelancephp
Tags: email, email address, mailto, encoder, encode, spam, protection, antispam, spambots, spamming
Requires at least: 2.8.0
Tested up to: 3.0.5
Stable tag: 0.2

Protect email addresses on your site from spambots and being used for spamming. This plugin encodes all email adresses so spambots cannot read them.

== Description ==

Protect email addresses on your site from spambots and being used for spamming. This plugin encodes all email adresses so spambots cannot read them.

Features:
* Encoding all emails: plain text, mailto links and the tags like `[encode_email email="..." display="..."]`
* Scanning posts, widgets and comments
* Immediatly ready after install
* Choose one of the high-quality encoding methods
* Supports querystrings like 'info@myemail.com?subject=Plugin'
* Tag available `[encode_email email="info@myemail.com" display="My Email"]`
* Template functions available `<?php echo encode_email( 'info@myemail.com', 'My Email' ); ?>`
* Supports PHP4.3+ and up to latest WP version

Extra:
* Put a Email Encoder Form on your site
* Developers can add their own methods

[Authors plugin page](http://www.freelancephp.net/email-encoder-php-class-wp-plugin/)

== Installation ==

1. Upload `wp-email-encoder-bundle.zip` to the `/wp-content/plugins/` directory or add the plugin with 'Add Plugins' in the admin menu
1. Be sure the plugin is activated in the Plugins-list

= How to use =

Tags:
* `[encode_email email="..." display="..."]` Encode the given email, "display" is optional otherwise the email wil be used as display
* `[email_encoder_form]` Puts an encoder form in your post (check if the option is activated on this page)

Template functions:
* `<?php echo encode_email( 'info@myemail.com', 'My Email' ); ?>` Encode the given email, the second param is display and optional
* `<?php echo encode_email_filter( $content ); ?>` Filter the given content for emails to encode

== Frequently Asked Questions ==

= Which encoding method should I use? =

The `wp_antispambot` method uses the built-in function of WordPress and does not use any javascript.
Although JavaScript methods (like email_escape) are probably better protection against spambots.

[Do you have another question? Please ask me](http://www.freelancephp.net/contact/)

== Screenshots ==

1. Admin Settings Page

== Changelog ==

= 0.2 =
* Implemented internalization (including translation for nl_NL)
* Improved user-interface of the Admin Settings Page and the Encoder Form
* Added template function: encode_email_filter()
* Kept and added only high-quality encoding methods
* Refactored the code and changed method and var names within the classes
* Fixed bugs occured using anti_email_spam() and hide_email() method

= 0.12 =
* Nothing changed, but 0.11 had some errors because /methods directory was missing in the repository.

= 0.11 =
* also possible to use encode tag in widgets by activating the "filter widget" option

= 0.1 =
* Works with PHP4 and PHP5
* Methods: default_encode, wp_antispambot, anti_email_spam, email_escape, hide_email
* Use the tags: `[email_encode email=".." display=".."]`, `[email_encoder_form]`
* Template function: `email_encode()`

== Other Notes ==

= TODO =
I've got some nice ideas for the next version(s).
If you have a suggestion please [contact me](http://www.freelancephp.net/contact/)

= Credits =
* [John Godley](http://urbangiraffe.com) for the encode method 'JavaScript Email Splitter' which is taken from his plugin [Anti-email Spam](http://urbangiraffe.com/plugins/anti-email-spam/)
* [Adam Hunter](http://blueberryware.net) for the encode method 'JavaScript Escape' which is taken from his plugin [Email Spam Protection](http://blueberryware.net/2008/09/14/email-spam-protection/)
* [Faycal Tirich](http://faycaltirich.blogspot.com) for using the regular expression from his plugin [WP Emails Encoder](http://faycaltirich.blogspot.com/1979/01/fay-emails-encoder-plugin.html)
* [Tyler Akins](http://rumkin.com) for the encode method 'JavaScript ASCII Mixer'
