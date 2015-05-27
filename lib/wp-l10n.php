<?php
/*
 *  Activate language cookie and make switching locale possible
 */

/**
 * start the session, after this call the PHP $_SESSION super global is available
 */
function wp_blocked_langSimpleSessionStart() {
    if(!session_id())session_start();
    ob_start();
}
/**
 * add actions at initialization to start the session
 * and at logout and login to end the session
 */
add_action('init', 'wp_blocked_langSimpleSessionStart', 1);

/**
 * destroy the session, this removes any data saved in the session over logout-login
 */
function wp_blocked_langSimpleSessionDestroy() {
    session_destroy ();
    ob_flush();
}

/**
 * get a value from the session array
 * @param type $key the key in the array
 * @param type $default the value to use if the key is not present. empty string if not present
 * @return type the value found or the default if not found
 */
function wp_blocked_langSimpleSessionGet($key, $default='') {
    if(isset($_SESSION[$key])) {
        return $_SESSION[$key];
    }
}

function wp_blocked_langSimpleSessionSet($key, $value) {
    $_SESSION[$key] = $value;
}

/**
 * set a value in the session array
 * @param type $key the key in the array
 * @param type $value the value to set
 */
// LANGUAGE COOKIE
function wp_blocked_set_lang_cookie() {
    $initLang = wp_blocked_langSimpleSessionGet('l');
    $key = 'l';

    if(isset($_GET['l'])) {
		$l = esc_attr($_GET['l']);
		wp_blocked_langSimpleSessionSet($key, $l);
   } else {
        if(empty($initLang)) {
            wp_blocked_langSimpleSessionSet($key, WPLANG);
        }
    }
}
add_action( 'init', 'wp_blocked_set_lang_cookie');

// CHANGE LOCAL LANGUAGE
function wp_blocked_l18n( $locale ) {
    $selected_lang = wp_blocked_langSimpleSessionGet('l');
	$key = 'l';
	if ( isset( $_GET['l'] ) ) {
		$l = esc_attr($_GET['l']);
		$locale = $l;
	} else if ($selected_lang != WPLANG) {
		$locale = $selected_lang;
	}
	return $locale;
}
add_filter( 'locale', 'wp_blocked_l18n' );
?>
