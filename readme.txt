=== WP Blocked Censorship Monitoring ===

Contributors: veganist, gutschilla 
Tags: censorship, block, censorship, filtering, monitoring, ooni, openrightsgroup, openinternet
Requires at least: 3.0
Tested up to: 4.2.4
Stable tag: v0.3.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin provides a search form to check if URLs are filtered, blocked or censored. 

== Description ==

wp-blocked simply provides a query frontend to the Blocking Middleware for Wordpress.
It allows you to show a search box to search for blocked or censored URLs. The results are provided via the Blocking Middleware API. 
See what this plugin does on [https://censorship.exposed](https://censorship.exposed) or on [https://blocked.org.uk](https://blocked.org.uk).

The Blocking Middleware is a censorship monitoring API developed by the OpenRightsGroup and needs to be run on a server in order to allow the plugin to query its database. This plugin as well as the API are Free Software.
To install the Blocking Middleware server see the [Blocking Middleware code repository](https://github.com/openrightsgroup/Blocking-Middleware).
This is the [API's documentation](https://wiki.openrightsgroup.org/wiki/Censorship_Monitoring_Project_API).

This plugin supports the use of the polylang multilanguage plugin.

== Installation ==

1. Unzip and upload `/wp-blocked/` to the `/wp-content/plugins/` directory
2. Make sure you have php5-curl installed on your webserver.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin through the wp-admin interface -> Settings -> WP Blocked Settings. This is where you will enter your API credentials.
5. Now you can add the [blocked_test_url] shortcode whereever you want to provide a search form for blocked URLs.
6. In the settings, you'll need to provide a page ID to which the search form redirects in order to display the results. On this page, please add the [blocked_test_url] shortcode. 
6a. If you use polylang, you will be able to provide a result page for every language.
7. In order to display statistics, you can use the [blocked_display_stats] shortcode anywhere on your website.

== Screenshots ==

1. On this screenshot you can see the plugin in action, using a global query to all installations.
2. This image explains how the plugin works.
3. Plugin configuration page.

== Changelog ==

= v0.3.3 =

Results may now be retrieved globally from different installations in different countries

= v0.3.2 =

* Initial release
