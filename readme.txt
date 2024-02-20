=== Normalizer ===
Contributors: zodiac1978
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LCH9UVV7RKDFY
Tags: DNS, SPF, DMARC,
Requires at least: 4.4.0
Tested up to: 6.4
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a DNS section to the debug information and some DNS checks to the Health Check feature.

== Description ==

DNS Info adds a new section to the Site Health Info tab. This debug information helps to see those DNS settings, which are not very visible, but could be the reason for mail deliverabilty problems.

Besides those debug information two status checks are added. One for the existencve of a SPF record and one for the existence of a DMARC record. There are no checks about the record itself or if they are correct. They just need to be there.

== Installation ==

1. Upload the zip file from this plugin on your plugins page or search for `DNS Info` and install it directly from the plugin directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Done!

== Frequently Asked Questions ==

= I don't see any changes!? =

You need to look in Tools -> Site Health.

On the "Status" tab there are two more checks and on the "Info" tab there a whole new section called "DNS Settings" added.

= It doesn't show the correct values! =

At the moment this does not work correctly on subdomain installations. I am working on a fix!

== Screenshots ==

1. Newly added "DNS Settings" section
2. Failed SPF record check
3. Successful SPF record check

== Changelog ==

= 1.0.0 =
* Initial release