<?php
require_once dirname(__FILE__) . "/CurlWrapper.php";

class BlockedUrl {
    
    const VERSION = "0.3.0";

    // required key, email
    public  $api_key;
    public  $api_email;
    public  $api_version = '1.2';
    
    // the URL which is going to checked for being blocked
    public  $url;

    // configurable endpoints, TODO: configure only host
    public $url_submit;
    public $url_status;
    public $url_daily_stats;
    public $host;

    // these _<WHATEVER>_respose values will hold return values from requests 
    private $_push_response;
    private $_status_response;
    private $_daily_stats_response;

    private $verify_ssl;
    
    // GETTERS
    
    public function push_response() {
        return $this->_push_response;
    }
    
    public function status_response() {
        return $this->_status_response;
    }
    
    public function daily_stats_response() {
        return $this->_daily_stats_response;
    }
    
    // PUBLIC HELPERS 
    
    public function make_signature( $url ) {
        return hash_hmac('sha512', $url, $this->api_key );
    }

    private function curl_opts(){
        if ( ! $this->verify_ssl ){
            return array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false
            );
        }
        else {
            return array(
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true
           );
        }
        return array();
    }

    // this is a simple URL maker that takes the host from constructor
    // and creates https://<HOST>/<PATH_FOR_CALL_TYPE>
    //
    // make_url_for( <submit|status|daily_stats>[, false ] )
    // 
    // set $https param to false to create http instead of https
    public function make_url_for( $api_call_type, $https = true ){
        $white_list = array(
            "submit"      => '/submit/url',
            "status"      => '/status/url',
            "global"      => '/status/global/url',
            "daily_stats" => '/status/daily-stats'
        );
        $path = $white_list[ $api_call_type ];
        if ( ! $path ){
            throw 'Unknown api_call_type "' . $api_call_type . '"';
        }
        $scheme = 'http';
        if ( $https ){ 
            $scheme = 'https';
        }
        return $scheme . '://' . $this->host . '/' . $this->api_version . $path; 

    }

    
    // PUBLIC API
    public function __construct( $api_key, $api_email, $url, $host, $verify_ssl = true ) {
        if ( ! ( $api_key && $api_email && $url ) ){
            throw new Exception('Usage: "new BlockedUrl( <API-KEY>, <API-EMAIL>, <URL>);"');
        }
        $this->api_key    = $api_key;
        $this->api_email  = $api_email;
        $this->url        = $url;
        $this->verify_ssl = $verify_ssl;
        $this->host       = $host;

    }
    
    public function push_request() {
        
        $response = CurlWrapper::curl_post(
            $this->make_url_for('submit'),
            array(
                "email"     => $this->api_email,
                "url"       => $this->url,
                "signature" => $this->make_signature( $this->url ),
            ),
            $this->curl_opts()
            
        );
        
        if ( $response["error"] ) {
            throw new Exception( "push_request failed to call to curl with: " . $response['error']);
        }
        if ( $response["status"] == 201 ){
            $this->_push_response = json_decode( $response["body"], true );
            return $this;
        }
        
        throw new Exception("push_request failed with status " . $response['status'] . " - " . $response['body'] );        
        
    }
        
    public function get_status() {
        
        $response = CurlWrapper::curl_get(
            $this->make_url_for('status'),
            array(
                "email"     => $this->api_email,
                "url"       => $this->url,
                "signature" => $this->make_signature( $this->url ),
            ),
            $this->curl_opts()
        );
        
        if ( $response["error"] ){
            throw new Exception( "get_status failed to call to curl with: " . $response['error'] );
        }
        if( $response["status"] == 404 ) {
            // try to push first, then retry getting status
            return $this->push_request()->get_status();
        }
        if ( $response["status"] == 200 ){
            $this->_status_response = json_decode( $response['body'], true );
            return $this;
        }
        
        throw new Exception("Unhandled get_status error! Server returned: " . $json );
        
    }
 
    public function get_global_status() {

        $response = CurlWrapper::curl_get(
            $this->make_url_for('global'),
            array(
                "email"     => $this->api_email,
                "url"       => $this->url,
                "signature" => $this->make_signature( $this->url ),
            ),
            $this->curl_opts()
        );

        if ( $response["error"] ){
            throw new Exception( "get_status failed to call to curl with: " . $response['error'] );
        }
        if( $response["status"] == 404 ) {
            // try to push first, then retry getting status
            return $this->push_request()->get_global_status();
        }
        if ( $response["status"] == 200 ){
            $this->_status_response = json_decode( $response['body'], true );
            return $this;
        }

        throw new Exception("Unhandled get_status error! Server returned: " . $json );

    }

    public function get_daily_stats( $days ){

        $date = date('Y-m-d G:i:s'); // now
        $params = array(
            "email"     => $this->api_email,
            "signature" => $this->make_signature( $date ),
            "date"      => $date,
        );
        // days parameter is optional
        if ( $days ){
            $params['days'] = $days;
        }

        $response = CurlWrapper::curl_get(
            $this->make_url_for('daily_stats'),
            $params,
            $this->curl_opts()
        );

        if ( $response["error"] ){
            throw new Exception( "get_daily_stats failed to call to curl with: " . $response['error'] );
        }
        if ( $response["status"] == 200 ){
            $this->_daily_stats_response = json_decode( $response['body'], true );
            return $this;
        }
        
        throw new Exception("Unhandled get_daily_stats error! Server returned: " . $json );
    }
   
}
?>
