<?php
require_once "CurlWrapper.php";

class BlockedUrl {
    
    const VERSION = "0.0.4";

    public  $api_key;
    public  $api_email;
    public  $url;
    private $_push_response;
    private $_status_response;
    
    private $user_agent; 
    
    private $url_submit = 'https://213.108.108.176/1.2/submit/url';
    private $url_status = 'https://213.108.108.176/1.2/status/url';
    
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
    
    // PUBLIC API
    public function __construct( $api_key, $api_email, $url ) {
        if ( ! ( $api_key && $api_email && $url ) ){
            throw new Exception('Usage: "new BlockedUrl( <API-KEY>, <API-EMAIL>, <URL>);"');
        }
        $this->api_key   = $api_key;
        $this->api_email = $api_email;
        $this->url       = $url;
    }
    
    public function push_request() {
        
        $json = CurlWrapper::curl_post(
            $this->url_submit,
            array(
                "email"     => $this->api_email,
                "url"       => $this->url,
                "signature" => $this->make_signature( $this->url ),
            ),
            array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
            )
            
        );
        
        $this->_push_response = json_decode( $json, true );
        return $this;
        
    }
        
    public function get_status() {
        
        $json = CurlWrapper::curl_get(
            $this->url_status,
            array(
                "email"     => $this->api_email,
                "url"       => $this->url,
                "signature" => $this->make_signature( $this->url ),
            ),
            array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
            )
        );
        
        $response = json_decode( $json, true );
        
        if ( $response["success"] ){
            $this->_status_response = $response;
            return $this;
        }
        
        if( ! $response["success"] ) {
            if ( preg_match( 'No matches in DB', $response["error"] ) ){
                // try to push first, then retry getting status
                return $this->push_request()->get_status();
            }
        }
        
        throw new Exception('Unhandled get_status error! Server returned: "' . $json );
        
    }
   
}
?>