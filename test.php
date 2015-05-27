<?php

/*
SYNOPIS

*/

$API_KEY;
$API_EMAIL;
$URL_SUBMIT;
$URL_STATUS;
require "secret-test.php"; // load $API_KEY, $API_EMAIL, $URL_SUBMIT, $URL_STATUS or set above

require "lib/BlockedUrl.php";

// oh yeah, gloabls.
$b = new BlockedUrl( $API_KEY, $API_EMAIL, 'http://twitter.com', false, $URL_SUBMIT, $URL_STATUS );

// simply check for given equals excepted and print some fail/success messages
function assert_equal( $given, $expected, $message ){
    if( $given === $expected ){
        echo $message . " - SUCCESS \n";
    }
    else {
        echo $message . " - FAIL, (given: " . $given . ", expected: " . $expected . ") \n";
    }
}

// check push_request() - sending URL to network
$result = $b->push_request()->push_response();
assert_equal( $result["success"],  true, "push_request" );
assert_equal( $result["uuid"] > 0, true, "uuid returned" );
assert_equal( isset( $result["queued"] ), true, "queued or not exists" );

// check get_status() - retrieve reuslts from network
$result = $b->get_status()->status_response();
assert_equal( $result["success"], true, "get_status" );
assert_equal( $result["url"], 'http://twitter.com', "twitter URL" );
assert_equal( count( $result["results"] ) > 0, true, "there are results in here" );
?>
