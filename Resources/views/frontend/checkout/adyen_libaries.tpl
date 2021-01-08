{block name='frontend_checkout_payment_content_adyen_libaries'}
    {if $sAdyenGoogleConfig}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}
    <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.19.0/adyen.js"
            integrity="sha384-taz+R7EBgv7Zhi89Jt8k00cAmOCtxDK586pbjJhl+owvWp7aipA2nawhG8VmPoVP"
            crossorigin="anonymous"></script>
    <link rel="stylesheet"
            href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.18.2/adyen.css"
            integrity="sha384-i7r6Qx/VBv4AYkHxh2Ah3fZd/+4scf5x72ZX6X1rrsxoPrvyr9lGOwacyysZDLWJ"
            crossorigin="anonymous">
{/block}