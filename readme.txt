=== WP Blocked ===

Contributors: veganist, mgutsch 
Tags: censorship, block, censorship, filtering, monitoring, ooni, openrightsgroup, openinternet
Requires at least: 3.0
Tested up to: 4.2.4
Stable tag: v0.3.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Suggests: polylang

Censorship monitoring: This plugin provides a search form to check if URLs are blocked or censored. Results are provided via the Blocking Middleware API.

== Description ==

wp-blocked provides a search box and statistics to search for blocked or censored URLs. Results are provided via the Blocking Middleware API [https://github.com/openrightsgroup/Blocking-Middleware]. The Blocking Middleware is a censorship monitoring API developed by the OpenRightsGroup and needs to be run on a server in order to allow the plugin to query its database.
The original implementation of Blocking Middleware and PHP is visible on https://blocked.org.uk.
wp-blocked simply provides a query frontend for Wordpress, but requires to install the Blocked Middleware independently.

API: https://wiki.openrightsgroup.org/wiki/Censorship_Monitoring_Project_API

== Installation ==

1. Unzip and upload `/wp-blocked/` to the `/wp-content/plugins/` directory
2. Make sure you have php5-curl installed on your webserver.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin through the wp-admin interface -> Settings -> WP Blocked Settings. This is where you will enter your API credentials.
5. Now you can add the [blocked_test_url] shortcode whereever you want to provide a search form for blocked URLs.
6. In the settings, you'll need to provide a page ID to which the search form redirects in order to display the results. On this page, please add the [blocked_test_url] shortcode.
7. In order to display statistics, you can use the [blocked_display_stats] shortcode anywhere on your website.

== Changelog ==

= v0.3.3 =

Results may now be retrieved globally.

= v0.3.2 =

* Initial release
