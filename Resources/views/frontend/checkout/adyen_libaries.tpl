{block name='frontend_checkout_payment_content_adyen_libaries'}
    {if $sAdyenGoogleConfig}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}
    <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.12.1/adyen.js"
            integrity="sha384-Z40LrT7R1YX9m5TJsqwQA5H3YqKvPA/DKBnPwXa4SwaDEs/feQSThsSph6PjbCQ1"
            crossorigin="anonymous"></script>
    <link rel="stylesheet"
            href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.12.1/adyen.css"
            integrity="sha384-GYuZ2hTudNw7WyFFpYgZ2+Dd1a1QqD0d0u7p6RE9F6q2yNnIEe6gPNs+Ml0QI5Mt"
            crossorigin="anonymous">
{/block}