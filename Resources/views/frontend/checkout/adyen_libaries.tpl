{block name='frontend_checkout_payment_content_adyen_libaries'}
    {if $sAdyenGoogleConfig}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}
    <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.8.1/adyen.js"
            integrity="sha384-pLfJ6XKllmblOK86IVevGarh2cfeBr6lWAEkumlMA3hgTqKpEgNn8ID7zq4HsC6H"
            crossorigin="anonymous"></script>

    <link rel="stylesheet"
          href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.8.1/adyen.css"
          integrity="sha384-y1lKqffK5z+ENzddmGIfP3bcMRobxkjDt/9lyPAvV9H3JXbJYxCSD6L8TdyRMCGM"
          crossorigin="anonymous">
{/block}