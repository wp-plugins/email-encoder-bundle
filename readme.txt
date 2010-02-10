=== Email Encoder Bundle ===
Contributors: freelancephp
Donate link: http://www.freelancephp.net/email-encoder/
Tags: email, encode, spam, protection, antispam, spambots, mailto
Requires at least: 2.7.0
Tested up to: 2.9.1
Stable tag: 0.1

Protecting emails from spambots and spamming by encoding them with one of the encode methods.

== Description ==

Protecting emails from spambots and spamming by encoding them with one of the encode methods.
Easy to use.

You can easily also put an email encoder form on your own site.

Developers can also add their own methods.

== Installation ==

1. Upload `wp-email-spam-protect.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[encode_email email="youremail@domain.com" display="Mail me"]` in a post
1. OR place `<?php encode_emal( 'youremail@domain.com', 'Mail me' ); ?>` in your templates

If you want to put an email encoder form on your site. Activate this option on the admin option page and put this code in your post: `[email_encoder_form]`

== Frequently Asked Questions ==

= Which method should I use? =

The `wp_antispambot` method uses the built-in function of WordPress and does not use any javascript.
Although JavaScript methods (like email_escape) are probably harder for spambots to understand.
You could also use the `random` function.

= How can I add my own method? =

You can choose your own name for the new encode function, f.e. func_name(). The function should have 3 arguments: $email, $display and $encode_display.
The argument $encode_display (boolean) means if the given $display should be encoded as well. In most cases you can use:
`if ( $encode_display )
		$display = Lim_Email_Encoder::get_htmlent( $display );`

Create a PHP file in the directory /methods, which contains the encode function. Give the file the same name as the function, e.g. `func_name.php`.
Now your function will be loaded automatically.

[Do you have another question? Please ask me](http://www.freelancephp.net/email-encoder/)

== Screenshots ==

1. Admin option page

== Changelog ==

= 0.1 =
* Works with PHP4 and PHP5
* Methods: default_encode, wp_antispambot, anti_email_spam, email_escape, hide_email
* Use the tags: `[email_encode email=".." display=".."]`, `[email_encoder_form]`
* Template function: `email_encode()`

== Credits ==

Credit goes to:

* [John Godley](http://urbangiraffe.com) for the encode method anti_email_spam() which is taken from his plugin [Anti-email Spam](http://urbangiraffe.com/plugins/anti-email-spam/)
* [Maurits van der Schee](http://www.maurits.vdschee.nl) for the encode method hide_email()
* [Adam Hunter](http://blueberryware.net) for the encode method email_escape() which is taken from his plugin [Email Spam Protection](http://blueberryware.net/2008/09/14/email-spam-protection/)
* [Faycal Tirich](http://faycaltirich.blogspot.com) for using the regular expression from his plugin [WP Emails Encoder](http://faycaltirich.blogspot.com/1979/01/fay-emails-encoder-plugin.html)

== Upgrade Notice ==

= 0.1 =
The first release.
