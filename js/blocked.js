jQuery(document).ready(function() {
    // automatically prepend http:// if user did not do it
    jQuery("#wp_blocked_url").change(function() {
        if (!/^http:\/\//.test(this.value)) {
            this.value = "http://" + this.value;
        }
   });

  // check if we are on the results page
  if(jQuery('#blocked-results').length > 0 && jQuery('#blocked-results div.error').length < 1) {
	  // if there are no results, we try to reload after n milliseconds.
	  // then check again. if on 3rd try there is nothing, we give up and display an error message.
	  var tries = 0;

	  // retrieve URL from search input
	  var blockedurl = jQuery('#wp_blocked_url').val();

	  // check if table has more than 0 results
	  var resultRows = jQuery('.url-results tbody').find('tr').length;

	  // show loader directly if there are no results at all.
	  if(!resultRows || resultRows < 1 && tries < 10) {
	      jQuery('#blocked-results-loader').fadeIn('fast');
	  }

	  var reload = setInterval(function(){
	    // reload 10 times or until we have at least 2 results
	    if(!resultRows || resultRows < 2) {
		if(tries < 10) {
		    // on each try check table rows
		    resultRows = jQuery('.url-results tbody').find('tr').length;
		    jQuery.ajax({
			beforeSend: function() {
			    jQuery('#blocked-results-loader').fadeIn('fast');
			},
			url : myAjax.ajaxurl,
			method: "POST",
			data: { action: "reload_blocked_results", wp_blocked_url: blockedurl },
			dataType: "text",
			cache: false,
			error: function (xhr, ajaxOptions, thrownError) {
			    console.log("Error: " + xhr.status + thrownError);
			},
			success: function(response) {
			    jQuery('#table-results').html(response);
			    if(resultRows > 0) {
				jQuery('#blocked-results-loader').fadeOut('slow');
			    }
			}
		    });
		    tries++;
		    console.log("Trying to load more results: " + tries);
		} else {
		    jQuery('#blocked-results-loader').html("Can't load more results.").css('background-image', 'none');
		    console.log("Can't load more results.");
		    clearInterval(reload);
		}
	    } else {
            }
	  }, 5000);
  }
});
