{extends file="frontend/checkout/confirm.tpl"}

{block name='frontend_index_content_left'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="frontend_index_after_body"}
    {$smarty.block.parent}

    {include file="frontend/checkout/adyen_libaries.tpl"}
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div id="payment" class="grid_20 adyen-additional-action-container"
         data-adyen-payment-additional-action
         data-checkoutConfigUrl="{url module='frontend' controller='AdyenPaymentProcess' action='getCheckoutConfig'}"
         data-additionalDataUrl="{url
         module='frontend'
         controller='AdyenPaymentProcess'
         action='handleAdditionalData'
         signature="{$basketSignature}"
         reference="{$orderReference}"
         }"
         data-checkoutShippingPaymentUrl="{url controller='checkout' action='shippingPayment' sTarget='checkout'}"
         data-additionalActionSelector="#adyen-additional-action"
    ></div>
    <script id="adyen-additional-action" type="application/json">{$action|json_encode}</script>

    <div class="doublespace">&nbsp;</div>
{/block}
