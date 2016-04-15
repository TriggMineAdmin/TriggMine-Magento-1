require(["fpModule/fingerprint", "uaParserModule/ua-parser", "tmModule/triggmine"], function(fingerprint, uaparser, tm) {
    var fp = new fingerprint();
    var uap = new uaparser();
    var tm = new tm();
    console.log(tm.getTriggMineData(uap));
    fp.get(function(result) {
        console.log(result);
    });
});
