<?php
class CurlWrapper {
    // based on comment on http://php.net/manual/de/function.curl-exec.php
    // extended with parts and ideas of https://github.com/Mashape/unirest-php/blob/master/src/Unirest/Request.php

    /** 
    * Send a POST requst using cURL 
    * @param string $url to request 
    * @param array $post values to send 
    * @param array $options for cURL 
    * @return array( code => $code, body => $body, error => $error, header => $header )
    */ 
    function curl_post($url, array $post = NULL, array $options = array()) { 
        $defaults = array( 
            CURLOPT_POST           => 1, 
            CURLOPT_HEADER         => true, 
            CURLOPT_URL            => $url, 
            CURLOPT_FRESH_CONNECT  => 1, 
            CURLOPT_RETURNTRANSFER => 1, 
            CURLOPT_FORBID_REUSE   => 1, 
            CURLOPT_TIMEOUT        => 10, 
            CURLOPT_POSTFIELDS     => http_build_query($post) 
        );
        return CurlWrapper::curl_process( $options, $defaults );    
    }

    /** 
    * Send a GET requst using cURL 
    * @param string $url to request 
    * @param array $get values to send 
    * @param array $options for cURL 
    * @return string 
    */ 
    function curl_get($url, array $get = NULL, array $options = array()) {    
        $defaults = array( 
            CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
            CURLOPT_HEADER => 1, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => 4 
        );
        
        return CurlWrapper::curl_process( $options, $defaults );    

    }     
    
    function curl_process( $options, $defaults ){
        
        $handle = curl_init(); 
        curl_setopt_array( $handle, ($options + $defaults)); 

        $response = curl_exec( $handle);
        $error    = curl_error( $handle);
        $info     = curl_getinfo( $handle );

        curl_close($handle);
        
        // Split the full response in its headers and body
        $header_size = $info['header_size'];
        $header      = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);
        $status      = $info['http_code'];
        
        return array(
            "header" => $header,
            "body"   => $body,
            "status" => $status,
            "error"  => $error,
        );
    }
        
}
?>