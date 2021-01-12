{block name='frontend_checkout_payment_content_adyen_libaries'}
    {if $sAdyenGoogleConfig}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}
    <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.17.2/adyen.js"
            integrity="sha384-9+nLpCVhoDOcPA/0Ebl0pTB55CIWp+XEMxjyivaRhvFc/Unajqo+Q/7+8I6+MtEO"
            crossorigin="anonymous"></script>
    <link rel="stylesheet"
            href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.17.2/adyen.css"
            integrity="sha384-cZIV5piH3KzCM9VwntcX2yQ9zLS8xvo9f0pld8RJe7mUBO2GcsgEmXkc78rH/UA3"
            crossorigin="anonymous">
{/block}