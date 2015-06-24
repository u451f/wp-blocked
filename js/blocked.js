function checkURL(url) {
    var string = url.value;
    if (!~string.indexOf("http")){
        string = "http://" + string;
    }
    url.value = string;
    return url
}
