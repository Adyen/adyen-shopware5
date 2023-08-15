{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_error_messages'}
    <div data-adyen-checkout-error="true">
        {if $sErrorMessages}{include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}{/if}
    </div>
    {$smarty.block.parent}
{/block}

{block name="frontend_checkout_confirm_left_payment_method"}
    {if $sUserData.additional.payment.image}
        <div class="payment--method-image">
            <img src="{$sUserData.additional.payment.image}"  alt="{$sUserData.additional.payment.description}"/>
        </div>
    {/if}

    {$smarty.block.parent}
{/block}

{block name='frontend_checkout_confirm_information_wrapper'}
    {$smarty.block.parent}

    {if $sPayment.isAdyenPaymentMethod}
        <input
            type="hidden"
            name="adyenPaymentMethodStateData"
            data-checkoutConfigUrl="{url module='frontend' controller='AdyenPaymentProcess' action='getCheckoutConfig'}"
            data-checkoutShippingPaymentUrl="{url controller='checkout' action='shippingPayment' sTarget='checkout'}"
        >
    {/if}
{/block}

{block name="frontend_index_after_body"}
    {$smarty.block.parent}

    {if $sPayment.isAdyenPaymentMethod}
        {include file="frontend/checkout/adyen_libaries.tpl"}

        <div
                data-adyenPaymentMethodType="{$sPayment.adyenPaymentType}"
                data-checkoutConfigUrl="{url module='frontend' controller='AdyenPaymentProcess' action='getCheckoutConfig'}"
                data-additionalDataUrl="{url module='frontend' controller='AdyenPaymentProcess' action='handleAdditionalData'}"
                data-adyen-confirm-order>
        </div>
    {/if}

{/block}

{block name="frontend_index_after_body"}
    {$smarty.block.parent}

    {if $sPayment.adyenPaymentType == 'googlepay' || $sPayment.adyenPaymentType == 'paywithgoogle'}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}

{/block}
