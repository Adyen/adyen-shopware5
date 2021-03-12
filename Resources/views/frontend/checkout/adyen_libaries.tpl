{block name='frontend_checkout_payment_content_adyen_libaries'}
    {if $sAdyenGoogleConfig}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}
    <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.23.0/adyen.js"
            integrity="sha384-fErYEDZTTzeO2aEcuTtsB49H+E6rwz7ODY7lMfb2/H9WKf20W0KaMbQFS17q6YE/"
            crossorigin="anonymous"></script>
    <link rel="stylesheet"
            href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.23.0/adyen.css"
            integrity="sha384-2ZygKOnU23yWkltsfDRsIMKXUBpYbr9Jx8kmIcgRnnoniBN6hwnyCr87XoYVv8Ux"
            crossorigin="anonymous">
{/block}