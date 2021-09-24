{block name='frontend_checkout_payment_content_adyen_libaries'}
    {if $sAdyenGoogleConfig}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}
    <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.1.0/adyen.js"
            integrity="sha384-3tEepwhhMcyxgIbL3HBe3I59BpSMNyKoNrbKWARYH1tJ7K7K6NdTDqOltKlwiVsH"
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/4.1.0/adyen.css"
            integrity="sha384-+CPzBNZVkBXu4uXDECnVuVQ24Kl8vWrR61UzuuuUj5IBEP//BQ0G0KDNfz2iPcvJ"
            crossorigin="anonymous">
{/block}
