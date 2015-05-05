<?php
/*
Plugin Name: WP Blocked
Plugin URI: http://github.com/iu451f/wp-blocked
Description:
Version: 1.0
Author: Ulrike Uhlig, Martin Gutsch
Author URI: http://curlybracket.net
License: GPL2+
*/

/*
    Copyright 2015 Ulrike Uhlig <u@curlybracket.net>, Martin Gutsch <gutsch@zwoelf.net>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require "lib/BlockedUrl.php";

function show_results() {
	$blocked = new BlockedUrl ( '<API_KEY>', '<API_EMAIL>', '<URL_TO_TEST>' );
	$blocked = new BlockedUrl ( '<API_KEY>', '<API_EMAIL>', '<URL_TO_TEST>', false ); // disable SSL peer verification

	// push your URL to network, and fetch response
	$pushed = $blocked->push_request()->push_response();

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
}


// todo: create shortcode for query form and result

// todo: create configuration page where we can translate 5 results: ok, blocked, error, dns-error, timeout

?>
