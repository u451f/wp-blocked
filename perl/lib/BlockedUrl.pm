package BlockedUrl;

our $VERSION = 0.10000;

=head1 NAME

BlockedURL

=head1 VERSION

0.2.3

=head1 DESCRIPTION

Minimal URL submit/status implementation of Censorship Monitoring Project API

=head1 SYNOPSIS

	use BlockedUrl;
	my $blocked = BlockedUrl->new(
		api_key   => '<API_KEY>',
		api_email => '<API_EMAIL>',
		url       => '<URL_TO_TEST>' 
	);
	
	# push your URL to network
	my $pushed = $blocked->push_request->push_response;
	
	# yields:
	# {
	# 	hash    => '<hash>',
	# 	queued  => 1, # or 0
	# 	success => 1, # or 0,
	# 	uuid    => '<uuid>'
	# }

	# retrieve URL status. 
	my $status = $blocked->get_status->status_response;	
	
	# yields:
	# {
	# 	categories => [ <strings>, ... ],
	# 	results    => [ 
	# 		{
	# 			blocktype 		=> 'what',
	# 			category  		=> 'ever',
	# 			first_blocked_timestamp => '2015-03-19 12:39:48',
	# 			last_blocked_timestamp  => '2015-03-19 12:39:48',
	# 			network_name            => 'Fake ISP Ltd',
	#			status                  => 'ok',
	#			status_timestamp        => '2015-04-30 22:46:54'
	# 		},
	# 		...
	# 	]
	
=head1 SSL Warning!

Currently, SSL_verify_mode is set to SSL_VERIFY_NONE, so we currently cannot be sure of who we're talking to. 

=head1 METHODS

=head2 url( <string> )

Sets/gets the URL to check

=head2 push_request()

Performs a push of the instance's url to the network. Results can be retrieved from push_response().

Returns self, dies on all errors.

=head2 push_response()

returnse the parsed JSON answer of last successful push_request()

=head2 get_status()

Tries to get the status for current URL from network. If this fails with a 404 status it tries to push the URL to
the network first, then retries. Result can be retrieved from status_response().

Returns self, dies on all other errors.

=head2 status_Returns self, desponse()

returnse the parsed JSON answer of last successful get_status()

=cut

use Moose;
use LWP::UserAgent;
use HTTP::Request::Common;
# See: https://metacpan.org/pod/Furl#HTTPS-requests-claims-warnings
use IO::Socket::SSL; # Thanks to Furl library to detail on this
use Digest::SHA;
use JSON::XS;

has api_key         => ( is => 'ro', isa => 'Str', required => 1 );
has api_email       => ( is => 'ro', isa => 'Str', required => 1 );
has url             => ( is => 'rw', isa => 'Str', required => 1 );
has push_response   => ( is => 'rw', isa => 'Maybe[HashRef]' );
has status_response => ( is => 'rw', isa => 'Maybe[HashRef]' );

has user_agent => ( 
	is => 'rw', 
	isa => 'LWP::UserAgent', 
	default => sub { 
		LWP::UserAgent->new(
			# wohho, self signed certificate!
			ssl_opts => { 
				verify_hostname => 0, # whould work as described in LWP::UserAgent
				SSL_verify_mode => SSL_VERIFY_NONE(), # now, this works
			}
		) 
	} 
); 

has url_submit => ( is => 'rw', isa => 'Str', default => 'https://213.108.108.176/1.2/submit/url' );
has url_status => ( is => 'rw', isa => 'Str', default => 'https://213.108.108.176/1.2/status/url' );

sub make_signature {
	my ( $self, $url ) = @_;
	return Digest::SHA::hmac_sha512_hex( $url, $self->api_key );
}

sub push_request {
	my ( $self ) = @_;
	my $request = POST(
		$self->url_submit,
		Content_Type => 'form-data',
		Content      => [	
			email     => $self->api_email,
			url       => $self->url,
			signature => $self->make_signature( $self->url ),
		]
	);
	my $response = $self->user_agent->request( $request );
	if ( $response->is_success ){
		$self->push_response( JSON::XS->new->decode( $response->content ) );
		return $self;
	}
	else {
		die 'Push request failed with  ' . $response->code . ' - '  . $response->message;
	}
}

sub make_get_query_url {
	my ( $self, $url, %params ) = @_;
	return $url . '?' . join('&', 
		map  { $_ . '=' . $params{$_} }
		keys %params 
	)
}

sub get_status {
	my ( $self ) = @_;
	my $request = GET(
		$self->make_get_query_url( 
			$self->url_status, (
				email     => $self->api_email,
				url       => $self->url,
				signature => $self->make_signature( $self->url ),
			)
		),
	);
	my $response = $self->user_agent->request( $request );
	if ( $response->is_success ){
		$self->status_response( JSON::XS->new->decode( $response->content ) );
		return $self;
	}
	elsif ( $response->code == 404) {
		# not in DB, try to push first
		warn 'Status request failed with  ' . $response->code . ' - '  . $response->message . '; trying to push first';
		return $self->push_request->get_status;
	}
	else {
		die 'Status request failed with  ' . $response->code . ' - '  . $response->message . '; trying to push first';
	}
	
}

1;
