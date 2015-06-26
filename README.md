# WP-Blocked

A wordpress plugin to check for blocked URLs querying the Blocked Middleware by OpenRightGroup. 
Depends on php-lib-blocked-url (included) and php5-curl.
We also use the polylang Wordpress plugin for l10n, but it is not required.

# BlockedUrl

A simple library that lets you submit URLs to it and fetch a result later. Implemented in Perl, PHP and as Wordpress plugin.

- Pure PHP implementation can be found [here](https://github.com/u451f/wp-blocked/tree/pure-php)
- Perl implementation can be found [here](https://github.com/u451f/blocked-perl)

- - -
# Documentation

## NAME

BlockedUrl

## VERSION

0.3.0

## DESCRIPTION

Minimal URL submit/status implementation of Censorship Monitoring
Project API / Blocked Middleware.

## SYNOPSIS

## Wordpress plugin

 * Make sure you have php5-curl installed on your webserver.
 * To install the Wordpress plugin for the repository and copy the wp-blocked folder to wp-content/plugins.
 * Activate the plugin through the Wordpress admin interface.
 * Configure the plugin through the wp-admin interface -> Settings -> WP Blocked Settings. This is where you will enter your API credentials.
 * You can add the `[blocked_test_url]` shortcode whereever you want to provide a search form for blocked URLs.
 * In the settings, you'll need to provide a page ID to which the search form redirects in order to display the results. On this page, please add the `[blocked_test_url]` shortcode.
 * The main plugin file is wp-blocked.php. It uses everything in lib/ and language/.
	
### Simple PHP implementation

    require "lib/BlockedUrl.php";

    $blocked = new BlockedUrl ( '<API_KEY>', '<API_EMAIL>', '<URL_TO_TEST>', <HOST> );
    $blocked = new BlockedUrl ( '<API_KEY>', '<API_EMAIL>', '<URL_TO_TEST>', <HOST>, false ); // disable SSL peer verification

    // push your URL to network, and fetch response
    my $pushed = $blocked->push_request()->push_response();

    // yields:
    // array(
    //       "hash"    => string,
    //       "queued"  => bool,
    //       "success" => bool,
    //       "uuid"    => int
    // )

    // retrieve URL status
    $status = $blocked->get_status()->status_response();

    // yields:
    // array(
    //       "url-status" => string( "ok"|"blocked" ),
    //       "categories" => array( string ),
    //       "results"    => array(
    //            blocktype               => 'what',
    //            category                => 'ever',
    //            first_blocked_timestamp => '2015-03-19 12:39:48',
    //            last_blocked_timestamp  => '2015-03-19 12:39:48',
    //            network_name            => 'Fake ISP Ltd',
    //            status                  => 'ok',
    //            status_timestamp        => '2015-04-30 22:46:54'
    //               ...
    //       )
    // )

## METHODS

### constructor( string $api_key, string, $api_email, string $url, string $host, boolean $ssl_verification = true )

$api_key, $api_email, $host and the $url are mandatory parameters. Set
$ssl_verification to false if you wish to call to hosts with self-signed SSL certificates.

The $host is either an IP or hostname string identifying the machine on which the server to query runs on. Currently only API Version 1.2 is supported. URL maming scheme is:

`https://<HOST>/1.2/<API-ENDPOINT>` 

whereas "1.2" and supported endpoints are hard-coded.

### url( <string> )

Sets/gets the URL to check.

### push_request()

Performs a push of the instance's url to the network. Results can be retrieved
from push_response().

Returns $this, throws exception on all errors.

### push_response()

Returns the parsed JSON answer of last successful push_request()

### get_daily_stats([ int ])

Calls to daily-stats facility. Returns object instance. To get reponse, call to
daily_stats_response() afterwards

`$blocked->get_daily_stats( 10 )->daily_stats_response()`

### get_status()

Tries to get the status for current URL from network. If this fails with
a 404 status it tries to push the URL to the network first, then
retries. Result can be retrieved from status_response().

Returns $this, throws exception on all other errors.

### status_response()

Returns the parsed JSON answer of last successful get_status()

### daily_stats_response()

Returns the parsed JSON anwer of last successful get_daily_stats()


