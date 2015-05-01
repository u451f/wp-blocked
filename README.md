# WP-Blocked 

A wordpress plugin to check for blocked URLs. Depends on php-lib-blocked-url (included).

# Components

## perl BlockedUrl

A simple Perl library that lets you submit URLs to it and fetch a result later.

## php BlockedURL

A PHP port of the Perl library above. It's been done this way as the author knows Perl way better than PHP. Porting is simpler than implementing. I may be wrong.

### php Documentation

Currently, see [perl/README.txt](https://github.com/u451f/wp-blocked/blob/master/perl/README.txt).

### php Synopsis (draft)

Will look pretty much the same as the perl one, except for named parameters in the constructer which aren't supported here:

	var $blocked_url = new BlockedUrl("<API_KEY>", "<API_EMAIL>", "<URL>");
	$blocked_url->push_request();    // sends URL to network (or fails)
	$blocked_url->push_response();   // returns parsed JSON response
	$blocked_url->get_status();      // tries to fetch response from network
	$blocked_url->status_response(); // returns status for URL (if has_status is true)
