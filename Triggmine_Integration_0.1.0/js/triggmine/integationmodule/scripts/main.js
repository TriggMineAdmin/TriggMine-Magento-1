require(["fpModule/fingerprint2", "jsCookie/jscookie", "json3/json3.min", "restful/restful.min", "clientJs/client.min"],

    function (fingerprint, Cookies, JSON3, RestFul) {
        var options = {
            swfPath: '/assets/FontList.swf',
            excludeUserAgent: true,
            excludeLanguage: true
        };

        var client = new ClientJS();
        new fingerprint(options).get(function (result) {





            var dc = client.getFingerprint();


            // var data = [
            //     {'device_id': [result, dc]},
            //     {'user_agent': client.getUserAgent()},
            //     {'referrer': document.referrer},
            //     {'location': document.location.href}
            // ];

            var data = {
                "prospect_id": "dsg",
                "order_id": "sdg",
                "device_id": "sdh",
                "device_id_1": "sdg",
                "price_total": "ewt",
                "qty_total": 2,
                "products": [
                    {
                        "product_id": "string",
                        "product_name": "string",
                        "product_sku": "string",
                        "product_image": "string",
                        "product_url": "string",
                        "product_qty": 0,
                        "product_price": "string",
                        "product_total_val": "string"
                    }
                ]
            };
            Cookies.set("device_id", result, {path: '/', expires: 7});
            Cookies.set("device_id_1", dc, {path: '/', expires: 7});
           // Cookies.set("data", data, {path: '/', expires: 7});





            const api = RestFul('http://site2.api.triggmine.com.ua/acc/api/events/cart/onFullCartChange', false);
            const articleMember = api.one();
            var promise = articleMember.get();
            promise.then(function (response) {
                console.log(response);
            })
                .then(function (reject) {
                    console.log(reject)
                })
            ;
            // promise.then()
            // console.log(promise);

            // const articlesCollection = api.all();



            // articlesCollection.post(data).then(function (response) {
            //     console.log(response);
            // });

            // console.log(articlesCollection);


        //  const articlesCollection = api.all('accumulator/page');
        // console.log(articlesCollection);
        //  var articleEntity = response.body();
        //  console.log(articleEntity.ErrorCode);
        //  console.log(articleEntity.LogId);
        //  });
        });
    });

