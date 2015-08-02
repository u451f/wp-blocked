function checkURL(url) {
    var string = url.value;
    if (!~string.indexOf("http")){
        string = "http://" + string;
    }
    url.value = string;
    return url;
}

jQuery(document).ready(function() {
  //setInterval(function(){
    jQuery.ajax({
        beforeSend: function() {
            jQuery("#table-results").append('<div id="blocked-results-loader />"');
        },
        url : myAjax.ajaxurl,
        method: "POST",
// fixme url et ssl
        data: {action: "reload_blocked_results", url: "http://gmail.com", ssl: false },
        dataType: "text",
        cache: false,
        error: function (xhr, ajaxOptions, thrownError) {
            console.log("Error: " + xhr.status + thrownError);
        },
        success: function(response) {
            jQuery('#blocked-results').html(response);
        }
    });
  //}, 2000);
});
