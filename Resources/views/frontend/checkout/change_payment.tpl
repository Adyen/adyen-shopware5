{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    {block name='frontend_checkout_payment_content_adyen_libaries'}
        <script src="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.1.0/adyen.js"></script>
        <link rel="stylesheet" href="https://checkoutshopper-live.adyen.com/checkoutshopper/sdk/3.0.0/adyen.css"/>
    {/block}

    {$smarty.block.parent}
{/block}