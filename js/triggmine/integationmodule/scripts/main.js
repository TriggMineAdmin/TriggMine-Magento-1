/*global require*/
require(["Fingerprint2", "jsCookie", "ClientJS"], function (Fingerprint2, Cookies) {
    
    /*global ClientJS*/
    var a = {
            swfPath: '/assets/FontList.swf',
            excludeUserAgent: !0,
            excludeLanguage: !0,
            extendedJsFonts: 0
        },
        b = new ClientJS(),
        c = b.getFingerprint();
        
    Cookies.set('device_id_1', c, {
        path: '/',
        expires: 7
    }), new Fingerprint2(a).get(function(a) {
        Cookies.set('device_id', a, {
            path: '/',
            expires: 7
        });
    });
    
});