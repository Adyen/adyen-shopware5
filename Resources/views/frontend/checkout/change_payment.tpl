{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    {include file="frontend/checkout/adyen_libaries.tpl"}

    {if $sAdyenConfig}
        <div data-adyenOriginKey='{$sAdyenConfig.originKey}'
             data-adyenEnvironment='{$sAdyenConfig.environment}'
             data-adyenPaymentMethodsResponse='{$sAdyenConfig.paymentMethods}'
             class="adyen-payment-selection">
        </div>
    {/if}

    {$smarty.block.parent}
{/block}