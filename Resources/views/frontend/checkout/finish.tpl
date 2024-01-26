{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_index_after_body"}
    {$smarty.block.parent}

    {include file="frontend/checkout/adyen_libaries.tpl"}
{/block}

{block name='frontend_checkout_finish_teaser_actions'}
    <div id="payment" class="grid_20 adyen-additional-action-container"
         data-adyen-payment-additional-action
         data-checkoutConfigUrl="{url module='frontend' controller='AdyenPaymentProcess' action='getCheckoutConfig'}"
         data-additionalDataUrl="{url
         module='frontend'
         controller='AdyenPaymentProcess'
         action='handleAdditionalData'
         }"
         data-checkoutShippingPaymentUrl="{url controller='checkout' action='shippingPayment' sTarget='checkout'}"
         data-additionalActionSelector="#adyen-additional-action"
         data-skipRedirect="1"
    ></div>
    <script id="adyen-additional-action" type="application/json">{$adyenAction}</script>
    {if $sErrorMessages}
        <div data-adyen-donation-error="true" class="finish--content">
            {include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
        </div>
    {/if}
    {if $sSuccessMessages}
        <div data-adyen-donation-success="true" class="finish--content">
            {include file="frontend/_includes/messages.tpl" type="success" content=$sSuccessMessages}
        </div>
    {/if}

    {$smarty.block.parent}
{/block}

{block name='frontend_checkout_finish_teaser_actions'}
    <div id='donation-container'
         data-donationsConfigUrl="{url
         module='frontend'
         controller='AdyenDonations'
         action='getDonationsConfig'
         merchantReference="{$merchantReference}"}"
         data-makeDonationsUrl="{url
         module='frontend'
         controller='AdyenDonations'
         action='makeDonations'
         merchantReference="{$merchantReference}"
         }"
    ></div>
{/block}
