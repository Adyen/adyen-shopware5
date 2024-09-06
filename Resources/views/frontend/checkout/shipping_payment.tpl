{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name="frontend_index_after_body"}
    {$smarty.block.parent}

    {include file="frontend/checkout/adyen_libaries.tpl"}
{/block}

{block name="frontend_index_content"}
    {s namespace="frontend/adyen/checkout" name="payment/adyen/update_payment_info_button_text" assign="snippetUpdatePaymentInfoButtonText"}{/s}
    <div
            data-updatePaymentInfoButtonText="{if $snippetUpdatePaymentInfoButtonText}{$snippetUpdatePaymentInfoButtonText|escape}{else}Update your payment information{/if}"
            data-checkoutConfigUrl="{url module='frontend' controller='AdyenPaymentProcess' action='getCheckoutConfig'}"
            data-adyen-payment-selection>
    </div>


    <div id="shipping_payment_wrapper" class="adyen-hidden--all">
        {$smarty.block.parent}
    </div>
    <input type="hidden" id="adyenClickToPayUrl" value="{url
    module='frontend'
    controller='AdyenExpressCheckout'
    action='finish'}">
{/block}
