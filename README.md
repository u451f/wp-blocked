# WP-Blocked

A wordpress plugin to check for blocked URLs. Depends on php-lib-blocked-url (included) and php5-curl.

# BlockedUrl

A simple library that lets you submit URLs to it and fetch a result later. Implemented in Perl and PHP.

- - -
# Documentation

## NAME

BlockedUrl

## VERSION

0.2.3

## DESCRIPTION

Minimal URL submit/status implementation of Censorship Monitoring
Project API

## SYNOPSIS
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

