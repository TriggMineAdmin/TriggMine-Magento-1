require(["fpModule/fingerprint", "jsCookie/jscookie"], function (fingerprint, Cookies) {
    var options = {
        swfPath: '/assets/FontList.swf',
        excludeUserAgent: true,
        excludeLanguage: true
    };
    new fingerprint(options).get(function (result) {
        Cookies.set("device_id", result, {path: '/', expires: 7});
        console.log(result);
    });
});
