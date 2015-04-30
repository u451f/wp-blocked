# WP-Blocked 

A wordpress plugin to check for blocked URLs. Depends on php-lib-blocked-url (included).

## php-lib-blocked-url

A simple PHP library that lets you submit URLs to it and fetch a result later.

## SYNOPSIS (draft)

	var $blocked_url = new BlockedUrl("http://example.com");
	$blocked_url->push_request(); 	// sends URL to network
	$blocked_url->fetch_reponse();	// tries to fetch response from network
	$blocked_url->has_response();   // returns true when response is ready
	$blocked_url->response();       // returns response (simple dict)
