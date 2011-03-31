=== Email Encoder Bundle ===
Contributors: freelancephp
Tags: email, hide, mailto, spam, protection, spambots, encoder, encrypt, encode, obfuscate, antispam, spamming
Requires at least: 2.7.0
Tested up to: 3.1
Stable tag: 0.30

Protect email addresses on your site from spambots and being used for spamming. This plugin encodes all email adresses so spambots cannot read them.

== Description ==

Protect email addresses on your site from spambots and being used for spamming. This plugin encodes all email adresses so spambots cannot read them.

= Features =
* Protect plain emails and mailto links
* Scanning posts, widgets, comments and RSS feeds
* Choose one of the high-quality encoding methods
* Supports querystrings like 'info@myemail.com?subject=Plugin'

* Tag available `[encode_email email="info@myemail.com" display="My Email"]`
* Template function available `<?php echo encode_email( 'info@myemail.com', 'My Email' ); ?>`

= Extra =
* Put an Email Encoder Form on your site
* Developers can add their own methods

Supports PHP4.3+ and up to latest WP version.

== Installation ==

1. Go to `Plugins` in the Admin menu
1. Click on the button `Add new`
1. Search for `Email Encode Bundle` and click 'Install Now' or click on the `upload` link to upload `email-encode-bundle.zip`
1. Click on `Activate plugin`

= Tags =
* `[encode_email email="..." display="..."]` Encode the given email, "display" is optional otherwise the email wil be used as display
* `[email_encoder_form]` Puts an encoder form in your post (check if the option is activated on this page)

= Template functions =
* `<?php echo encode_email( 'info@myemail.com', 'My Email' ); ?>` Encode the given email, the second param is display and optional
* `<?php echo encode_email_filter( $content ); ?>` Filter the given content for emails to encode

== Frequently Asked Questions ==

= Which encoding method should I use? =

The `Html Encode` method uses the built-in function of WordPress and does not use any javascript.
Although JavaScript methods (like `JavaScript ASCII`) are probably better protection against spambots.

= I want to make some adjustment in one of the encoding methods. What is the best way? =

The best way is to make a copy of that method and make your adjustments in the copy. Give the new method a unique name.
Now you can keep updating this plugin and keep remaining the changes you have made.

= My self-written method doesn't work after upgrading to v0.2. How to fix this? =

The has been some changes to the structure of the encoding methods.
The first is the 3rd param `$encode_display` has been removed, because the display should always be encoded.
Second, the methodnames should contain the prefix `lim_email_`.
Optionally you can add a name and description to be showed in the admin panel, like:
`$lim_email_yourmethodname = array( 'name' => 'YourMethodName',	'description' => '....' );`

[Do you have another question? Please ask me](http://www.freelancephp.net/contact/)

== Screenshots ==

1. Admin Options Page
1. Email Encoder Form on the Site

== Changelog ==

= 0.31 =
* Fixed tiny bug (incorrect var-name $priority on line 100 of email-encoder-bundle.php)

= 0.30 =
* Added protection for emails in RSS feeds
* Improved filtering tags [encode_email ... ]
* Improved ASCII and Escape method and added noscript message
* Solved an option bug (encode mailto links VS encode plain emails)
* Made some cosmetical adjustments on the options page
* Code refactoring

= 0.22 =
* First decodes entities before encoding email
* Added more wp filters for encoding

= 0.21 =
* Changed Encoder Form: HTML markup and JavaScript
* Made some minor adjustments and fixed little bugs

= 0.20 =
* Implemented internalization (including translation for nl_NL)
* Improved user-interface of the Admin Settings Page and the Encoder Form
* Added template function: encode_email_filter()
* Kept and added only high-quality encoding methods
* Refactored the code and changed method- and var-names within the classes
* Removed 3rd param $encode_display out of the encoding methods, display should always be encoded
* Added prefix 'lim_email_' to the encoding methods

= 0.12 =
* Nothing changed, but 0.11 had some errors because /methods directory was missing in the repository.

= 0.11 =
* also possible to use encode tag in widgets by activating the "filter widget" option

= 0.10 =
* Works with PHP4 and PHP5
* Methods: default_encode, wp_antispambot, anti_email_spam, email_escape, hide_email
* Use the tags: `[email_encode email=".." display=".."]`, `[email_encoder_form]`
* Template function: `email_encode()`

== Other Notes ==

= Credits =
* [Adam Hunter](http://blueberryware.net) for the encode method 'JavaScript Escape' which is taken from his plugin [Email Spam Protection](http://blueberryware.net/2008/09/14/email-spam-protection/)
* [Tyler Akins](http://rumkin.com) for the encode method 'JavaScript ASCII Mixer'
* Title icon on Admin Options Page was made by [Jack Cai](http://www.doublejdesign.co.uk/)

== Upgrade Notice ==

= 0.30 =
* Some bug fixes
* New: email protection for RSS feeds
