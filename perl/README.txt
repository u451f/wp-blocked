NAME
    BlockedUrl

VERSION
    0.2.3

DESCRIPTION
    Minimal URL submit/status implementation of Censorship Monitoring
    Project API

SYNOPSIS
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
            #       hash    => '<hash>',
            #       queued  => 1, # or 0
            #       success => 1, # or 0,
            #       uuid    => '<uuid>'
            # }

            # retrieve URL status. 
            my $status = $blocked->get_status->status_response;     
        
            # yields:
            # {
            #       categories => [ <strings>, ... ],
            #       results    => [ 
            #               {
            #                       blocktype               => 'what',
            #                       category                => 'ever',
            #                       first_blocked_timestamp => '2015-03-19 12:39:48',
            #                       last_blocked_timestamp  => '2015-03-19 12:39:48',
            #                       network_name            => 'Fake ISP Ltd',
            #                       status                  => 'ok',
            #                       status_timestamp        => '2015-04-30 22:46:54'
            #               },
            #               ...
            #       ]

METHODS
  url( <string> )
    Sets/gets the URL to check

  push_request()
    Performs a push of the instance's url to the network. Currently,
    SSL_verify_mode is set to SSL_VERIFY_NONE, so we cannot currently be
    sure of who we're talking to. Results can be retrieved from
    push_response().

    Returns self, dies on all errors.

  push_response()
    returnse the parsed JSON answer of last successful push_request()

  get_status()
    Tries to get the status for current URL from network. If this fails with
    a 404 status it tries to push the URL to the network first, then
    retries. Result can be retrieved from status_response().

    Returns self, dies on all other errors.

  status_Returns self, desponse()
    returnse the parsed JSON answer of last successful get_status()

