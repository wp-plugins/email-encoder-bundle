=== Email Encoder Bundle ===
Contributors: freelancephp
Tags: email, hide, mailto, spam, protection, spambots, encoder, encrypt, encode, obfuscate, antispam, spamming
Requires at least: 2.7.0
Tested up to: 3.5.0
Stable tag: 0.50

Protect email addresses on your site from spambots and being used for spamming. This plugin encodes all email adresses so spambots cannot read them.

== Description ==

Protect email addresses on your site from spambots and being used for spamming. This plugin encodes all email adresses so spambots cannot read them.

= Features =
* Protect plain emails and mailto links
* Encode all kind of content (text and html)
* Scanning posts, widgets, comments and RSS feeds
* Choose one of the high-quality encoding methods
* Supports querystrings like 'info@myemail.com?subject=Plugin'
* Put an Email Encoder Form on your site

= Tags =
* `[encode_email email="..." display="..." method="..."]` Encode the given email, "display" is optional otherwise the email wil be used as display
* `[encode_content method="..."]...[/encode_content]` Encode content, "method" is optional otherwise the method set in the admin options page
* `[email_encoder_form]` Puts an encoder form in your post (check if the option is activated on this page)

= Template functions =
* `<?php echo encode_email( $email, [ $display ], [ $method ], [ $extra_attrs ] ); ?>` Encode the given email, the other params are optional
* `<?php echo encode_content( $content, [ $method ] ); ?>` Encode the given content for emails to encode
* `<?php echo encode_email_filter( $content, [ $enc_tags ], [ $enc_mailtos ], [ $enc_plain_emails ] ); ?>` Filter the given content for emails to encode, the other params are optional

= Support =
Supports PHP4.3+ and up to latest WP version.

== Installation ==

1. Go to `Plugins` in the Admin menu
1. Click on the button `Add new`
1. Search for `Email Encode Bundle` and click 'Install Now' or click on the `upload` link to upload `email-encode-bundle.zip`
1. Click on `Activate plugin`

== Frequently Asked Questions ==

= How do I encode my emailaddress(es)? =

By default the option `Encode mailto links` is enabled and the default method is `JavaScript ASCII`. This means the following html snippet:
`<a href="mailto:myname@test.nl">My Email</a>`

Will be encoded, which creates the following output in the source code of your page:
`<script type="text/javascript">/*<![CDATA[*/ML="mo@k<insc:r.y=-Ehe a\">f/lMt";MI="4CB8HC77=D0C5HJ1>H563DB@:AF=D0C5HJ190<6C0A2JA7J;6HDBBJ5JHA=DI<B?0C5HDEI<B?0C5H4GCE";OT="";for(j=0;j<MI.length;j++){OT+=ML.charAt(MI.charCodeAt(j)-48);}document.write(OT);/*]]>*/</script><noscript>*protected email*</noscript>`

Therefore spambots are not able to scan this emailaddress from the site.

Within your posts, you can use the shortcode `[email_encode]`, f.e.:
`[email_encode email="myname@test.nl" display="My Email"]`

= Which encoding method should I use? =

The `Html Encode` method uses the built-in function of WordPress and does not use any javascript.
Although JavaScript methods (like `JavaScript ASCII`) are probably better protection against spambots.

= How to create mailto links that opens in a new window? =

You could use add extra params to the mailto link and add `target='_blank'`, f.e.:
`[encode_email email="yourmail@test.nl" display="My Mail" extra_attrs="target='_blank'"]`

= How to encode emails in ALL widgets? =

If the option 'All text widgets' is activated, only all widgets will be filtered for encoding.
It's possible to encode emails in all widgets by using the Widget Logic plugin and activate the 'widget_content' filter.

[Do you have another question? Please ask me](http://www.freelancephp.net/contact/)

== Screenshots ==

1. Admin Options Page
1. Email Encoder Form on the Site

== Changelog ==

= 0.50 =
* Added encode method for all kind of contents (template function and shortcode "encode_content")
* Added extra param for additional html attributes (f.e. target="_blank")
* Added option to skip certain posts from being automatically encoded
* Added option custom protection text
* Removed "method" folder. Not possible to add own methods anymore.
* Other small changes and some refactoring

= 0.42 =
* Widget Logic options bug

= 0.41 =
* Solved bug by improving regular expression for mailto links
* Changed script attribute `language` to `type`
* Script only loaded on options page (hopefully this solves the dashboard toggle problem some people are experiencing)
* Added support for widget_content filter of the Logic Widget plugin

= 0.40 =
* Added option for setting CSS classes
* Improved RSS protection
* Removed Lim_Email_Encoder class (now all handled by the main class)
* Enabled setting checkbox for filtering posts
* Fixed PHP / WP notices
* Added param for encode methods: $obj

= 0.32 =
* Fix IE bug
* Bug plain emails
* Optional "method" param for tag and template function, f.e. [encode_email email="test@domain.com" method="ascii"]
* Small adjustments

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

= 0.50 =
* Added encode method for all kind of contents
* Added extra param for additional html attributes
* Added option to skip certain posts from being automatically encoded
* Added option custom protection text
* Notice: not possible to add your own methods anymore.
