=== Email Encoder Bundle ===
Contributors: freelancephp
Donate link: http://www.freelancephp.net/email-encoder/
Tags: email, email address, mailto, encoder, encode, spam, protection, antispam, spambots, spamming
Requires at least: 2.7.0
Tested up to: 2.9.1
Stable tag: 0.10

Encoding email adresses to protect them from spambots and being used for spamming.

== Description ==

Encoding email adresses to protect them from spambots and being used for spamming.

* Automatically encodes all email adresses (plain text and mailto links)
* Put an email encoder form on your own site.
* Choose the prefered method (or on every request randomly pick one of the methods)
* Add your own methods.
* Easy to use.

== Installation ==

1. Upload `wp-email-encoder-bundle.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[encode_email email="youremail@domain.com" display="Mail me"]` in a post
1. OR place `<?php encode_emal( 'youremail@domain.com', 'Mail me' ); ?>` in your templates

If you want to put an email encoder form on your site. Activate this on the admin option page and put this code in your post: `[email_encoder_form]`

== Frequently Asked Questions ==

= Which method should I use? =

The `wp_antispambot` method uses the built-in function of WordPress and does not use any javascript.
Although JavaScript methods (like email_escape) are probably better protection agains spambots.
You could also use the `random` function to randomly pick a method on every page request.

= How can I add my own method? =

You can choose your own name for the new encode function, f.e. func_name(). The function should have 3 arguments: $email, $display and $encode_display.
The 3th argument $encode_display (boolean) tells you if the function also needs to encode $display. In most cases you can just use this code for encoding the display:
`if ( $encode_display ) {`
`	$display = Lim_Email_Encoder::get_htmlent( $display );`
`}`

Create a PHP file in the directory /methods, which contains the encode function. Give the file the same name as the function, e.g. `func_name.php`.
Now your function will be loaded automatically.

[Do you have another question? Please ask me](http://www.freelancephp.net/email-encoder/)

== Screenshots ==

1. Admin option page

== Changelog ==

= 0.10 =
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

= 0.10 =
The first release.
