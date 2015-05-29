<?php
require_once dirname(__FILE__) . "/CurlWrapper.php";

class BlockedUrl {
    
    const VERSION = "0.2.3";

    public  $api_key;
    public  $api_email;
    public  $url;
    public  $url_submit;
    public  $url_status;

    private $_push_response;
    private $_status_response;
    private $verify_ssl;
    
    // GETTERS
    
    public function push_response() {
        return $this->_push_response;
    }
    
    public function status_response() {
        return $this->_status_response;
    }
    
    // PUBLIC HELPERS 
    
    public function make_signature( $url ) {
        return hash_hmac('sha512', $url, $this->api_key );
    }

    public function make_get_query_url( $url, $params ) {
        // TODO: find out how to use map() in PHP ;-)
        $query_url = $url;
        $has_looped = false;
        foreach( $params as $key => $value ){
            $separator = '&';
            if ( ! $has_looped ){
                $separator = '?';
                $has_looped = true;    
            }
            $query_url = $query_url . $separator . $key . '=' . $value;
            
        }
        return $query_url;
    }
    
    private function curl_opts(){
        if ( ! $this->verify_ssl ){
            return array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false
            );
        } else {
            return array(
                CURLOPT_SSL_VERIFYHOST => 1,
                CURLOPT_SSL_VERIFYPEER => true
           );
	}
        return array();
    }
    
    // PUBLIC API
    public function __construct( $api_key, $api_email, $url, $verify_ssl = true, $url_submit, $url_status ) {
        if ( ! ( $api_key && $api_email && $url ) ){
            throw new Exception('Usage: "new BlockedUrl( <API-KEY>, <API-EMAIL>, <URL>);"');
        }
        $this->api_key    = $api_key;
        $this->api_email  = $api_email;
        $this->url        = $url;
        $this->verify_ssl = $verify_ssl;
    	$this->url_submit = $url_submit;
        $this->url_status = $url_status;
    }
    
    public function push_request() {
        
        $response = CurlWrapper::curl_post(
            $this->url_submit,
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
            $this->url_status,
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
   
}
?>
