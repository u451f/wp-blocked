function checkURL(url) {
    var string = url.value;
    if (!~string.indexOf("http")){
        string = "http://" + string;
    }
    url.value = string;
    return url;
}

jQuery(document).ready(function() {
  // check if table has more than 0 results
  var resultRows = jQuery('.url-results tbody').find('tr').length;
  if(!resultRows || resultRows < 4) { // <1!! fixme debug
    var blockedurl = jQuery('#wp_blocked_url').val();
    // fixme : get correct ssl value
    var blockedssl = false;
    //setInterval(function(){
        jQuery.ajax({
            beforeSend: function() {
                jQuery('#blocked-results-loader').show();
            },
            url : myAjax.ajaxurl,
            method: "POST",
            data: { action: "reload_blocked_results", url: blockedurl, ssl: blockedssl },
            dataType: "text",
            cache: false,
            error: function (xhr, ajaxOptions, thrownError) {
                console.log("Error: " + xhr.status + thrownError);
            },
            success: function(response) {
                jQuery('#table-results').html(response);
                jQuery('#blocked-results-loader').fadeOut();
            }
        });
    //}, 2000);
  }
});
