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


	require_once "lib/BlockedUrl.php";
function show_results($URL, $SSL=false) {

	// load $API_KEY, $API_EMAIL, $URL_SUBMIT, $URL_STATUS
	require_once "secret-test.php"; 
	$blocked = new BlockedUrl( $API_KEY, $API_EMAIL, $URL, $SSL, $URL_SUBMIT, $URL_STATUS ); // false = disable SSL peer verification

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
}

// simply check for given equals expected and print fail or success messages
function assert_equal( $given, $expected, $message ){
    if( $given === $expected ){
        echo $message . " - SUCCESS \n";
    }
    else {
        echo $message . " - FAIL, (given: " . $given . ", expected: " . $expected . ") \n";
    }
}

// todo: create shortcode for query form and result
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

        add_settings_section(
            'wp_blocked_section_l10n', // ID
            'WP Blocked Translations', // Title
            array( $this, 'print_section_info' ), // Callback
            'wp-blocked-settings' // Page
        );

        add_settings_field(
            'API_KEY',
            'API Key',
            array( $this, 'api_key_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
        add_settings_field(
            'API_EMAIL',
            'API Email',
            array( $this, 'api_email_callback' ),
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
            'l10n_blocked',
            'Translation for "blocked"',
            array( $this, 'l10n_blocked_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_l10n'
        );
        add_settings_field(
            'l10n_ok',
            'Translation for "ok"',
            array( $this, 'l10n_ok_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_l10n'
        );
        add_settings_field(
            'l10n_dns_error',
            'Translation for "DNS Error"',
            array( $this, 'l10n_dns_error_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_l10n'
        );
        add_settings_field(
            'l10n_error',
            'Translation for "error"',
            array( $this, 'l10n_error_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_l10n'
        );
        add_settings_field(
            'l10n_timeout',
            'Translation for "timeout"',
            array( $this, 'l10n_timeout_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_l10n'
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

        if( !empty( $input['l10n_blocked'] ) )
            $input['l10n_blocked'] = sanitize_text_field( $input['l10n_blocked'] );
        if( !empty( $input['l10n_ok'] ) )
            $input['l10n_ok'] = sanitize_text_field( $input['l10n_ok'] );
        if( !empty( $input['l10n_error'] ) )
            $input['l10n_error'] = sanitize_text_field( $input['l10n_error'] );
        if( !empty( $input['l10n_dns_error'] ) )
            $input['l10n_dns_error'] = sanitize_text_field( $input['l10n_dns_error'] );
        if( !empty( $input['l10n_timeout'] ) )
            $input['l10n_timeout'] = sanitize_text_field( $input['l10n_timeout'] );

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
            '<input type="text" id="API_EMAIL" name="wp_blocked_option_name[API_EMAIL]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['API_EMAIL'])
        );
    }
    
	public function api_key_callback() {
        printf(
            '<input type="text" id="API_KEY" name="wp_blocked_option_name[API_KEY]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['API_KEY'])
        );
    }

    public function url_submit_callback() {
        printf(
            '<input type="url" id="URL_SUBMIT" name="wp_blocked_option_name[URL_SUBMIT]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['URL_SUBMIT'])
        );
    }

    public function url_status_callback() {
        printf(
            '<input type="url" id="URL_STATUS" name="wp_blocked_option_name[URL_STATUS]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['URL_STATUS'])
        );
    }

    public function l10n_blocked_callback() {
        printf(
            '<input type="text" id="l10n_blocked" name="wp_blocked_option_name[l10n_blocked]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['l10n_blocked'])
        );
    }

    public function l10n_ok_callback() {
        printf(
            '<input type="text" id="l10n_ok" name="wp_blocked_option_name[l10n_ok]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['l10n_ok'])
        );
    }

    public function l10n_error_callback() {
        printf(
            '<input type="text" id="l10n_error" name="wp_blocked_option_name[l10n_error]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['l10n_error'])
        );
    }

    public function l10n_dns_error_callback() {
        printf(
            '<input type="text" id="l10n_dns_error" name="wp_blocked_option_name[l10n_dns_error]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['l10n_dns_error'])
        );
    }

    public function l10n_timeout_callback() {
        printf(
            '<input type="text" id="l10n_timeout" name="wp_blocked_option_name[l10n_timeout]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['l10n_timeout'])
        );
    }

}

if( is_admin() )
    $wp_blocked_settings_page = new wpBlockedSettingsPage();
?>
