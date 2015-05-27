<?php
/*
Plugin Name: WP Blocked
Plugin URI: http://github.com/iu451f/wp-blocked
Description:
Version: 1.0
Author: Ulrike Uhlig, Martin Gutsch
Author URI: http://curlybracket.net
License: GPL2+
Text Domain: wp-blocked 
Domain Path: /languages/
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

require_once "lib/wp-l10n.php";
require_once "lib/BlockedUrl.php";

/* l10n */
function wp_blocked_init() {
	 $plugin_dir = basename(dirname(__FILE__));
	 load_plugin_textdomain( 'wp-blocked', false, "$plugin_dir/languages" );
}
add_action('plugins_loaded', 'wp-blocked_init');

function show_results($URL, $SSL=false) {

	// load $API_KEY, $API_EMAIL, $URL_SUBMIT, $URL_STATUS via secret file
	// require_once "secret-test.php"; 
	// $blocked = new BlockedUrl( $API_KEY, $API_EMAIL, $URL, $SSL, $URL_SUBMIT, $URL_STATUS ); // false = disable SSL peer verification
	
	// load $API_KEY, $API_EMAIL, $URL_SUBMIT, $URL_STATUS via WP options
	$options = get_option('wp_blocked_option_name');

	if(!isset($options['API_KEY']) OR !isset($options['API_EMAIL']) OR !isset($options['URL_SUBMIT']) OR !isset($options['URL_STATUS'])) {
		// throw error
		echo __("Missing options.", 'wp-blocked');
	} else {
		$blocked = new BlockedUrl( $options['API_KEY'], $options['API_EMAIL'], $URL, $SSL, $options['URL_SUBMIT'], $options['URL_STATUS'] ); // false = disable SSL peer verification
		// push your URL to network, and fetch response
		$pushed = $blocked->push_request()->push_response();
		print_r($pushed);

		// yields:
		// array(
		//       "hash"    => string,
		//       "queued"  => bool,
		//       "success" => bool,
		//       "uuid"    => int
		// )

		// retrieve URL status
		$status = $blocked->get_status()->status_response();
		print_r($status);

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

		// possible results and their translation
		$status_blocked = __('blocked', 'wp-blocked');
		$status_ok = __('ok', 'wp-blocked');
		$status_error = __('error', 'wp-blocked');
		$status_dns_error = __('DNS error', 'wp-blocked');
		$status_timeout = __('timeout', 'wp-blocked');
	}
}

// create a shortcode which will insert a form [blocked_test_url]
// todo : treat the result
function wp_blocked_url_shortcode() {
       echo '<form method="POST"><input type="url" value="" name="wp_blocked_url" /><input type="submit" value="send" class="submit" /></form>';
}
add_shortcode( 'blocked_test_url', 'wp_blocked_url_shortcode' );

// todo: tmp tester
show_results('http://twitter.com');

// Create configuration page where we can translate 5 results: ok, blocked, error, dns-error, timeout
class wpBlockedSettingsPage {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'WP Blocked Settings',
            'manage_options',
            'wp-blocked-settings',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Set class property
        $this->options = get_option( 'wp_blocked_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e('Settings WP Blocked'); ?></h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'wp_blocked_option_group' );
                do_settings_sections( 'wp-blocked-settings' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
            'wp_blocked_option_group', // Option group
            'wp_blocked_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'wp_blocked_section_general', // ID
            'WP Blocked Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'wp-blocked-settings' // Page
        );
        add_settings_field(
            'API_EMAIL',
            'API Email',
            array( $this, 'api_email_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
        add_settings_field(
            'API_KEY',
            'API Key',
            array( $this, 'api_key_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
        add_settings_field(
            'URL_SUBMIT',
            'Submit URL',
            array( $this, 'url_submit_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
        add_settings_field(
            'URL_STATUS',
            'Status URL',
            array( $this, 'url_status_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
        add_settings_field(
            'languages',
            'Languages (separated by comma, use international abbreviations (ie. "fr" for french, "ar" for arabic.)',
            array( $this, 'languages_status_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {

        if( !empty( $input['API_KEY'] ) )
            $input['API_KEY'] = sanitize_text_field( $input['API_KEY'] );
        if( !empty( $input['API_EMAIL'] ) )
            $input['API_EMAIL'] = sanitize_email( $input['API_EMAIL'] );
        if( !empty( $input['URL_SUBMIT'] ) )
            $input['URL_SUBMIT'] = esc_url( $input['URL_SUBMIT'] );
        if( !empty( $input['URL_STATUS'] ) )
            $input['URL_STATUS'] = esc_url( $input['URL_STATUS'] );
        if( !empty( $input['languages'] ) ) {
            $input['languages'] = sanitize_text_field(str_replace( ';', ',', $input['languages'] ));
            $tmplanguages = explode( ',', $input['languages'] );
            foreach($tmplanguages as $language) {
                $tmp = sanitize_text_field( $language );
                if(!empty($tmp)) {
                    $clean_languages[] = $tmp;
                }
            }
            $input['languages'] = implode(',', $clean_languages);
	}
        return $input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info() {
        print _e('Please fill in the corresponding fields.');
    }

    /**
     * Get the settings option array and print one of its values
     */

    public function api_email_callback() {
        printf(
            '<input type="text" id="API_EMAIL" name="wp_blocked_option_name[API_EMAIL]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['API_EMAIL'])
        );
    }
    
	public function api_key_callback() {
        printf(
            '<input type="text" id="API_KEY" name="wp_blocked_option_name[API_KEY]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['API_KEY'])
        );
    }

    public function url_submit_callback() {
        printf(
            '<input type="url" id="URL_SUBMIT" name="wp_blocked_option_name[URL_SUBMIT]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['URL_SUBMIT'])
        );
    }

    public function url_status_callback() {
        printf(
            '<input type="url" id="URL_STATUS" name="wp_blocked_option_name[URL_STATUS]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['URL_STATUS'])
        );
    }

    public function languages_status_callback() {
        printf(
            '<input type="text" id="languages" name="wp_blocked_option_name[languages]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['languages'])
        );
    }
}

if( is_admin() )
    $wp_blocked_settings_page = new wpBlockedSettingsPage();
	?>
