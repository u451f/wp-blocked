<?php
/*
Plugin Name: WP Blocked
Plugin URI: http://github.com/u451f/wp-blocked
Description: Wordpress plugin to interact with the Blocked-Middleware by OpenRightsGroup. API credentials can be configured via a settings page.
Version: 1.1
Author: Ulrike Uhlig, Martin Gutsch
Author URI: http://curlybracket.net
License: GPL3+
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
function fetch_results($URL, $fetch_stats=false) {
	require_once "lib/BlockedUrl.php";
	$options = get_option('wp_blocked_option_name');

    // check for required options.
	if(empty($options['API_KEY']) OR empty($options['API_EMAIL']) OR empty($options['HOST'])) {
		// throw error
		echo __("Missing options.", 'wp-blocked');
	} else {
        // translate SSL option
        if (!$options['SSL'] || $options['SSL'] == "0") $SSL = False; else $SSL = True;
        // API change in BlockedURL 0.2.x => 0.3.0!
		$blocked = new BlockedUrl( $options['API_KEY'], $options['API_EMAIL'], $URL, $options['HOST'], $SSL ); // false = disable SSL peer verification

		// push your URL to network, and fetch response
		$pushed = $blocked->push_request()->push_response();
	
        	if (!$options['GLOBAL'] || $options['GLOBAL'] == "0") {
			// retrieve URL status form one country
			$status = $blocked->get_status()->status_response();
		} else  {
			// retrieve results from all installations	
			$status = $blocked->get_global_status()->status_response();
		}
		return $status;
	}
}

// create HTML output for status results
function format_results($URL, $fetch_stats=false) {
    $status = fetch_results($URL, $fetch_stats);
	if($status['success'] == 1) {
		// this means a general success of the request
		$output .= '<div id="blocked-results">'."\n";
		// create table
		$output .= '<div class="blocked-results-table-wrapper">'."\n";
		$output .= '<div id="table-results">'."\n";
		$output .= '<h2 class="url-searched">'.__("Results for", 'wp-blocked').' '. $URL.'</h2>'."\n";

		if($status['results'][0]['success'] || $status['results'][0]['error']) {
			// global request
			foreach ($status['results'] as $country) {
				if($country['error']) {
					$output .= '<div class="error">'.$country['country'].': '.$country['error'].'</div>';
				} else if($country['success'] == 1 && count($country['results']) > 0) {
					$output .= format_results_table($country['results'], $country['country']);
				}  else {
					$output .= '<div class="error">'.$country['country'].': '.__('No results', 'wp-blocked').'</div>';
				}
			}
		} else {
			// simple request
			if(count($status['results']) > 0) {
			    $output .= format_results_table($status['results']);
			}
		}

		$output .= '</div>'."\n";
		$output .= '<div id="blocked-results-loader"><span>'.__('Trying to load more results', 'wp-blocked').'</span><!-- --></div></div>'."\n";
		// add permalinks and links for sharing the result on social media
		$output .= '<p class="permlink">
			<a href="'.get_permalink($post->ID).'?wp_blocked_url='.$URL.'">'. __("Permalink for this result", 'wp-blocked').'</a><a href="https://twitter.com/home?status='.__('Check if this website being blocked:', 'wp-blocked').' '. $URL .'+'.get_permalink($post->ID).'?wp_blocked_url='.$URL.'" target="_blank"><i class="fa fa-twitter"></i> '.__('Share on Twitter', 'wp-blocked').'</a><a href="http://facebook.com.com/share.php?t='.__('Check if this website being blocked:', 'wp-blocked').' '. $URL .'&amp;u='.get_permalink($post->ID).'?wp_blocked_url='.$URL.'" target="_blank"><i class="fa fa-facebook"></i> '.__('Share on Facebook', 'wp-blocked').'</a>
			</p>'."\n";
        $output .= "</div>";
	} else {
		$output .= '<p class="error">'.__("Could not retrieve results.", 'wp-blocked').'</p>'."\n";
	}
	return $output;
}

// create HTML output for status results, result table
function format_results_table($results, $country = false) {
	$output .= '<table class="url-results">'."\n";
	$output .= '<thead><tr>'."\n";
	if($country !== false) $output .= '<th>'.__('Country', 'wp-blocked').'</th>'."\n";
	$output .= '<th>'.__('ISP', 'wp-blocked').'</th><th>'.__('Result', 'wp-blocked').'</th><th>'.__('Last check on', 'wp-blocked').'</th><th>'.__('Last block on', 'wp-blocked').'</th></thead>'."\n";
	foreach ($results as $result) {
		// load translations
		if($result['status'] == 'blocked') {$readable_status = __('blocked', 'wp-blocked');}
		else if($result['status'] == 'ok') {$readable_status = __('ok', 'wp-blocked');}
		else if($result['status'] == 'error') {$readable_status = __('error', 'wp-blocked');}
		else if($result['status'] == 'dns-error') {$readable_status = __('DNS error', 'wp-blocked');}
		else if($result['status'] == 'timeout') {$readable_status = __('timeout', 'wp-blocked');}
		else if($result['status'] == 'unknown') {$readable_status = __('unknown', 'wp-blocked');}

		// create css classes for rows
		$css_class = strtolower($result['status']);
		if($result['first_blocked_timestamp']) $css_class .= " prior-block";

		// if there is no first_blocked_ts this has never been blocked & we need to assign the current ts to last_blocked_ts
		$first_blocked_timestamp = $result['first_blocked_timestamp'] ?:  __('No record of prior block', 'wp-blocked');
		$last_blocked_timestamp = $result['last_blocked_timestamp'] ?: $result['status_timestamp'];

		// html output
		$output .= '<tr class="'.$css_class.'">'."\n";
		if($country !== false) $output .= '<td>'.$country.'</td>'."\n";
		$output .= '<td>'.$result['network_name'].'</td>'."\n";
		$output .= '<td>'.$readable_status.'</td>'."\n";
		$output .= '<td>'.$last_blocked_timestamp.'</td>'."\n";
		$output .= '<td>'.$first_blocked_timestamp.'</td>'."\n";
		$output .= '</tr>';
	}
	$output .= '</table>'."\n";

	return $output;
}

// this will only be called on the AJAX call
function reload_blocked_results() {
    if(isset($_POST['wp_blocked_url'])) $URL = esc_url($_POST['wp_blocked_url']);
    else if(isset($_GET['wp_blocked_url'])) $URL = esc_url($_GET['wp_blocked_url']);
    $status = fetch_results($URL, false);
    if(count($status['results']) > 0) {
        echo "<!-- reloaded URL: ". $status['url'] ." -->";
        echo format_results_table($status['results']);
    }
    wp_die();
}
add_action("wp_ajax_reload_blocked_results", "reload_blocked_results");
add_action("wp_ajax_nopriv_reload_blocked_results", "reload_blocked_results");

// add results to wp get_the_content function for the page ID specified in settings
function display_results() {
	global $post, $polylang;
	if (function_exists('pll_current_language')) {
		$curLocale = pll_current_language('locale');
	}
	$options = get_option('wp_blocked_option_name');
	if(isset($_POST['wp_blocked_url']) OR isset($_GET['wp_blocked_url']) && is_page($options["resultspage_$curLocale"])) {
		if(isset($_GET['wp_blocked_url'])) {
			$URL = esc_url($_GET['wp_blocked_url']);
		} else {
			$URL = esc_url($_POST['wp_blocked_url']);
		}
		$output = $post->post_content.'<hr />'.format_results($URL);
	} else {
		$output = $post->post_content;
	}
	return $output;
}
add_filter( 'the_content', 'display_results', 4, 0);

// fetch statistics based on Alexa ranking
function fetch_stats($URL) {
	require_once "lib/BlockedUrl.php";

	// load $API_KEY, $API_EMAIL, $HOST, $URL_STATUS via WP options
	$options = get_option('wp_blocked_option_name');

	if(empty($options['API_KEY']) OR empty($options['API_EMAIL']) OR empty($options['HOST'])) {
		// throw error
		echo __("Missing options.", 'wp-blocked');
	} else {
		$blocked = new BlockedUrl( $options['API_KEY'], $options['API_EMAIL'], $URL, $options['HOST'], $SSL=false );
		$stats = $blocked->get_daily_stats(4)->daily_stats_response();
		return $stats;
	}
}

// create a shortcode which will insert a form [blocked_test_url]
function wp_blocked_url_shortcode() {
	global $polylang;
	if (function_exists('pll_current_language')) {
		$curLocale = pll_current_language('locale');
	}

	$options = get_option('wp_blocked_option_name');
	if(isset($_GET['wp_blocked_url'])) $value = sanitize_url($_GET['wp_blocked_url']);
	else if(isset($_POST['wp_blocked_url'])) $value = sanitize_url($_POST['wp_blocked_url']);

	$form = '<form name="wp_blocked_form" class="form wp-blocked-form" method="POST" action="'.get_permalink($options["resultspage_$curLocale"]).'" validate autocomplete="on">';
	$form .= '<input placeholder="'. __('Test if this URL is blocked', 'wp-blocked').'" type="url" value="'.$value.'" id="wp_blocked_url" name="wp_blocked_url" required pattern="^https?://.+([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,9}$" title="'.__('URL format: http://example.com or https://secure.com', 'wp-blocked').'" /><input type="submit" value="'.__('send', 'wp-blocked').'" class="submit" /></form>';
	return $form;
}
add_shortcode( 'blocked_test_url', 'wp_blocked_url_shortcode' );

// implement a way to display statistics of blocked URLs
function wp_blocked_statistics_shortcode() {
	global $polylang;
	if (function_exists('pll_current_language')) {
		$curLocale = pll_current_language('locale');
	}

	if(isset($_GET['wp_blocked_url']) || isset($_POST['wp_blocked_url'])) {
		if(isset($_GET['wp_blocked_url'])) {
			$URL = sanitize_url($_GET['wp_blocked_url']);
		} else if(isset($_POST['wp_blocked_url'])) {
			$URL = sanitize_url($_POST['wp_blocked_url']);
		}

		$stats = fetch_stats($URL);
		if($stats && $stats['success'] == 1) {
			$html_output = '<h2 class="widget-title">'.__('Statistics', 'wp-blocked').'</h2>';
			foreach ($stats['stats'] as $date => $item) {
				$percent = 100/100000*$item['blocked'];
				$percent = number_format((float)$percent, 2, '.', '');
				$html_output .= '<div class="blocked-item">';
				$html_output .= '<span class="blocked_sites_percent">'.$percent.'%</span>';
				$html_output .= '<span class="date">'.$date.'</span>';
				$html_output .= '<span class="blocked_sites">'.$item['blocked'].' <i>'.__('blocked sites', 'wp-blocked').'</i></span>';
				$html_output .= '</div>';
			}
			return $html_output;
		}
	}
}
add_shortcode( 'blocked_display_stats', 'wp_blocked_statistics_shortcode' );

// call javascript & style
function blocked_scripts() {
	wp_enqueue_style( 'blocked', plugins_url('', __FILE__).'/css/blocked.css' );
	wp_register_script( 'blocked', plugins_url('', __FILE__).'/js/blocked.js', 0, 0, true );
    wp_localize_script( 'blocked', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'blocked' );
}
add_action( 'wp_enqueue_scripts', 'blocked_scripts' );

function get_languages() {
	// check configured languages via polylang plugin.
	global $polylang;
	if (isset($polylang)) {
		$languages = $polylang->get_languages_list();
		return $languages;
	}
}

// Create configuration page for wp-admin. Each domain shall configure their API_KEY, API_EMAIL, HOST and results page.
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
            'HOST',
            'HOST URL or IP (no protocol, no trailing slash, i.e. blocked.example.io)',
            array( $this, 'host_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
        add_settings_field(
            'SSL',
            'Use SSL to talk to the Middleware? Set this to no if Middleware server uses a self-signed certificate',
            array( $this, 'ssl_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );
        add_settings_field(
            'GLOBAL',
            'Retrieve results globally, from all installations',
            array( $this, 'global_callback' ),
            'wp-blocked-settings',
            'wp_blocked_section_general'
        );

	$languages = get_languages();
	if($languages) {
		foreach($languages as $lang) {
			add_settings_field(
			    'resultspage_'.$lang->locale,
			    'Page ID for results in '.$lang->name,
			    array( $this, 'resultspage_status_callback' ),
			    'wp-blocked-settings',
			    'wp_blocked_section_general',
			    array( 'locale' => $lang->locale )
			);
		}
	} else {
		add_settings_field(
		    'resultspage_',
		    'Page ID for results',
		    array( $this, 'resultspage_status_callback' ),
		    'wp-blocked-settings',
		    'wp_blocked_section_general'
		);
	}
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
    if( !empty( $input['HOST'] ) )
        $input['HOST'] = sanitize_text_field( $input['HOST'] );

	$languages = get_languages();
	if($languages) {
		foreach($languages as $lang) {
			$locale = $lang->locale;
			if( !empty( $input["resultspage_$locale"] ) ) {
			    $input["resultspage_$locale"] = sanitize_text_field( $input["resultspage_$locale"] );
			}
		}
	} else {
		if( !empty( $input['resultspage_'] ) ) {
		    $input['resultspage_'] = sanitize_text_field( $input['resultspage_'] );
		}
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

    public function host_callback() {
        printf(
            '<input type="text" id="HOST" name="wp_blocked_option_name[HOST]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['HOST'])
        );
    }

    public function ssl_callback() {
	    $options = get_option('wp_blocked_option_name');
        echo '<input name="wp_blocked_option_name[SSL]" id="SSL" type="checkbox" value="1" ' . checked( 1, $options['SSL'], false ) . ' /> SSL';
    }

    public function global_callback() {
	    $options = get_option('wp_blocked_option_name');
        echo '<input name="wp_blocked_option_name[GLOBAL]" id="GLOBAL" type="checkbox" value="1" ' . checked( 1, $options['GLOBAL'], false ) . ' /> global';
    }

    public function resultspage_status_callback($args) {
	$locale = $args['locale'];
	printf(
	    '<input type="number" id="resultspage_'.$locale.'" name="wp_blocked_option_name[resultspage_'.$locale.']" value="%s" class="regular-text ltr" required />',
	    esc_attr( $this->options["resultspage_$locale"])
	    );
    }
}

if( is_admin() )
    $wp_blocked_settings_page = new wpBlockedSettingsPage();
?>
