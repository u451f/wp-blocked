<?php
class BlockedUrl {

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
        //my $request = POST(
        //    $self->url_submit,
        //    Content_Type => 'form-data',
        //    Content      => [	
        //        email     => $self->api_email,
        //        url       => $self->url,
        //        signature => $self->make_signature( $self->url ),
        //    ]
        //);
        //my $response = $self->user_agent->request( $request );
        //if ( $response->is_success ){
        //    $self->push_response( JSON::XS->new->decode( $response->content ) );
        //    return $self;
        //}
        //else {
        //    die 'Push request failed with  ' . $response->code . ' - '  . $response->message;
        //}
    }
        
    public function get_status() {
        //my $request = GET(
        //    $self->make_get_query_url( 
        //        $self->url_status, (
        //            email     => $self->api_email,
        //            url       => $self->url,
        //            signature => $self->make_signature( $self->url ),
        //        )
        //    ),
        //);
        //my $response = $self->user_agent->request( $request );
        //if ( $response->is_success ){
        //    $self->status_response( JSON::XS->new->decode( $response->content ) );
        //    return $self;
        //}
        //elsif ( $response->code == 404) {
        //    # not in DB, try to push first
        //    warn 'Status request failed with  ' . $response->code . ' - '  . $response->message . '; trying to push first';
        //    return $self->push_request->get_status;
        //}
        //else {
        //    die 'Status request failed with  ' . $response->code . ' - '  . $response->message . '; trying to push first';
        //}
    }
   
}
?>