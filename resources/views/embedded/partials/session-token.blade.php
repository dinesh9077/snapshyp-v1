{{-- Include after jQuery on embedded pages: sends the Shopify session token with every
     jQuery AJAX call so ShopifyEmbeddedAuth can verify it (App Bridge already adds it to fetch) --}}
<script>
    (function () {
        var token = new URLSearchParams(window.location.search).get('id_token') || '';

        // Session tokens live about a minute; keep a fresh one ready
        function refresh() {
            if (window.shopify && shopify.idToken) {
                shopify.idToken().then(function (t) { token = t; });
            }
        }
        refresh();
        setInterval(refresh, 45000);

        if (window.jQuery) {
            jQuery.ajaxSetup({
                beforeSend: function (xhr) {
                    if (token) {
                        xhr.setRequestHeader('Authorization', 'Bearer ' + token);
                    }
                }
            });
        }
    })();
</script>
