{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    {include file="frontend/checkout/adyen_libaries.tpl"}

    {if $sAdyenConfig}
        <div data-shopLocale='{$sAdyenConfig.shopLocale}'
             data-adyenOriginKey='{$sAdyenConfig.originKey}'
             data-adyenEnvironment='{$sAdyenConfig.environment}'
             data-adyenPaymentMethodsResponse='{$sAdyenConfig.paymentMethods}'
             data-resetSessionUrl='{url controller="Adyen" action="ResetValidPaymentSession"}'
             {if $mAdyenSnippets}data-adyensnippets="{$mAdyenSnippets}"{/if}
             class="adyen-payment-selection">
        </div>
    {/if}

    {$smarty.block.parent}
{/block}

{block name='frontend_checkout_payment_fieldset_input_label'}
    {if $payment_mean.image}
        <div class="method--image">
            <img src="{$payment_mean.image}" alt="{$payment_mean.description}"/>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}