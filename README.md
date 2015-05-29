# WP-Blocked

A wordpress plugin to check for blocked URLs querying the Blocked Middleware by OpenRightGroup. 
Depends on php-lib-blocked-url (included) and php5-curl.

# BlockedUrl

A simple library that lets you submit URLs to it and fetch a result later. Implemented in Perl and PHP.

- Perl implementation van be found [here](https://github.com/u451f/blocked-perl)

- - -
# Documentation

## NAME

BlockedUrl

## VERSION

0.2.5

## DESCRIPTION

Minimal URL submit/status implementation of Censorship Monitoring
Project API / Blocked Middleware.

## SYNOPSIS
	
### Simple PHP implementation

    require "lib/BlockedUrl.php";

    $blocked = new BlockedUrl ( '<API_KEY>', '<API_EMAIL>', '<URL_TO_TEST>' );
    $blocked = new BlockedUrl ( '<API_KEY>', '<API_EMAIL>', '<URL_TO_TEST>', false ); // disable SSL peer verification

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

### Test

In order to test from the commandline, you'll need to create secret-test.php and configure the variables
$API_KEY, $API_EMAIL, $URL_SUBMIT, $URL_STATUS in there. Then simply run `php test.php`

### Wordpress plugin

 * Make sure you have php5-curl installed on your webserver.
 * To install the Wordpress plugin for the repository and copy the wp-blocked folder to wp-content/plugins.
 * Activate the plugin through the Wordpress admin interface.
 * Configure the plugin through the wp-admin interface -> Settings -> WP Blocked Settings. This is where you will enter your API credentials.
 * You can add the `[blocked_test_url]` shortcode whereever you want to provide a search form for blocked URLs.
 * In the settings, you'll need to provide a page ID to which the search form redirects in order to display the results. On this page, please add the `[blocked_test_url]` shortcode.
 * The main plugin file is wp-blocked.php. It uses everything in lib/ and language/.

## METHODS

### constructor( string $api_key, string, $api_email, string $url, boolean $ssl_verification = true )

$api_key, $api_email and the $url are mandatory parameters. Set
$ssl_verification to false if you wish to call to hosts with self-signed SSL certificates.

### url( <string> )

Sets/gets the URL to check.

### push_request()

Performs a push of the instance's url to the network. Results can be retrieved
from push_response().

Returns $this, throws exception on all errors.

### push_response()

Returns the parsed JSON answer of last successful push_request()

### get_status()

Tries to get the status for current URL from network. If this fails with
a 404 status it tries to push the URL to the network first, then
retries. Result can be retrieved from status_response().

Returns $this, throws exception on all other errors.

### status_response()

Returns the parsed JSON answer of last successful get_status()
