<?php
/*
Plugin Name: WP Blocked
Plugin URI: http://github.com/u451f/wp-blocked
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

/* Plugin l10n */
function wp_blocked_init() {
	 $plugin_dir = basename(dirname(__FILE__));
	 load_plugin_textdomain( 'wp-blocked', false, "$plugin_dir/languages" );
}
add_action('plugins_loaded', 'wp_blocked_init');

// fetch results from server
function fetch_results($URL, $SSL) {
	require_once "lib/BlockedUrl.php";

	// load $API_KEY, $API_EMAIL, $URL_SUBMIT, $URL_STATUS via WP options
	$options = get_option('wp_blocked_option_name');

	if(empty($options['API_KEY']) OR empty($options['API_EMAIL']) OR empty($options['URL_SUBMIT']) OR empty($options['URL_STATUS'])) {
		// throw error
		echo __("Missing options.", 'wp-blocked');
	} else {
		$blocked = new BlockedUrl( $options['API_KEY'], $options['API_EMAIL'], $URL, $SSL, $options['URL_SUBMIT'], $options['URL_STATUS'] ); // false = disable SSL peer verification

		// push your URL to network, and fetch response
		$pushed = $blocked->push_request()->push_response();
		// print_r($pushed);

		// yields:
		// array(
		//       "hash"    => string,
		//       "queued"  => bool,
		//       "success" => bool,
		//       "uuid"    => int
		// )

		// retrieve URL status
		$status = $blocked->get_status()->status_response();
		// print_r($status);

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

		return $status;
	}
}

// create HTML output for status results
function format_results($URL, $SSL=false) {
	$status = fetch_results($URL, $SSL);
	if($status['success'] == 1) {
		$output .= '<h2 class="url-searched">'.__("Results for").' '. $status['url'].'</h2>';
		$output .= '<h3 class="url-status">'.__("Status:").' '. $status['url-status'].'</h3>';
		if(count($status['results']) > 0) {
			$output .= '<table class="url-results">';
			$output .= '<thead><tr><th>'.__('ISP').'</th><th>'.__('Result').'</th><th>'.__('Last check on').'</th><th>'.__('Last block on').'</th></thead>';
			foreach ($status['results'] as $result) {
				// load translations
				if($result['status'] == 'blocked') {$readable_status = __('blocked', 'wp-blocked');}
				else if($result['status'] == 'ok') {$readable_status = __('ok', 'wp-blocked');}
				else if($result['status'] == 'error') {$readable_status = __('error', 'wp-blocked');}
				else if($result['status'] == 'dns-error') {$readable_status = __('DNS error', 'wp-blocked');}
				else if($result['status'] == 'timeout') {$readable_status = __('timeout', 'wp-blocked');}

				// create css classes for rows
				$css_class = strtolower($result['status']);
				if($result['first_blocked_timestamp']) $css_class .= " prior-block";

				// if there is no first_blocked_ts this has never been blocked & we need to assign the current ts to last_blocked_ts
				$first_blocked_timestamp = $result['first_blocked_timestamp'] ?:  __('No record of prior block');
				$last_blocked_timestamp = $result['last_blocked_timestamp'] ?: $result['status_timestamp'];
				
				// html output
				$output .= '<tr class="'.$css_class.'">';
				$output .= '<td>'.$result['network_name'].'</td>';
				$output .= '<td>'.$readable_status.'</td>';
				$output .= '<td>'.$last_blocked_timestamp.'</td>';
				$output .= '<td>'.$first_blocked_timestamp.'</td>';
				//$result['category']
				//$result['blocktype']
				$output .= '</tr>';
			}
			$output .= '</table>';
		}
	} else {
		$output .= '<p class="error">Could not retrieve results.</p>';
	}
	return $output;
}

function display_results() {
	global $post;
	$options = get_option('wp_blocked_option_name');
	if(isset($_POST['wp_blocked_url']) OR isset($_GET['wp_blocked_url']) && is_page($option['resultspage'])) {
		if(isset($_GET['wp_blocked_url'])) {
			$URL = sanitize_url($_GET['wp_blocked_url']);
		} else {
			$URL = sanitize_url($_POST['wp_blocked_url']);
		}
		// fixme: check if URL is SSL and if yes, then set $SSL to true
		$SSL = false;
		$output = $post->post_content.'<hr />'.format_results($URL, $SSL);
	} else {
		$output = $post->post_content;
	}
	return $output;
}
add_filter( 'the_content', 'display_results', 4, 0);

// create a shortcode which will insert a form [blocked_test_url]
function wp_blocked_url_shortcode() {
	$options = get_option('wp_blocked_option_name');
	if(isset($_GET['wp_blocked_url'])) $value = sanitize_url($_GET['wp_blocked_url']);
	else if(isset($_POST['wp_blocked_url'])) $value = sanitize_url($_POST['wp_blocked_url']);
    	
	$form = '<form method="POST" action="'.get_permalink($options['resultspage']).'" validate>';
	$form .= '<input  placeholder="'. __('Test if this URL is blocked').'" type="url" value="'.$value.'" name="wp_blocked_url" required /><input type="submit" value="send" class="submit" /></form>';
	return $form;
}
add_shortcode( 'blocked_test_url', 'wp_blocked_url_shortcode' );

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
            'resultspage',
            'Page ID for results',
            array( $this, 'resultspage_status_callback' ),
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
        if( !empty( $input['resultspage'] ) )
            $input['resultspage'] = sanitize_text_field( $input['resultspage'] );
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

    public function resultspage_status_callback() {
        printf(
            '<input type="number" id="resultspage" name="wp_blocked_option_name[resultspage]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['resultspage'])
        );
    }

    public function languages_status_callback() {
        printf(
            '<input type="text" id="languages" name="wp_blocked_option_name[languages]" value="%s" class="regular-text ltr" />',
            esc_attr( $this->options['languages'])
        );
    }
}

if( is_admin() )
    $wp_blocked_settings_page = new wpBlockedSettingsPage();
?>
