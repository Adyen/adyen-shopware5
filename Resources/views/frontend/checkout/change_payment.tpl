{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    {include file="frontend/checkout/adyen_libaries.tpl"}

    {block name='frontend_checkout_payment_adyen_order_total'}
        <input type="hidden" id="adyen_order_total" value="{if $sAmountWithTax && $sUserData.additional.charge_vat}{$sAmountWithTax}{else}{$sAmount}{/if}"/>
        <input type="hidden" id="adyen_order_currency" value="{$sBasket.sCurrencyName}"/>
    {/block}

    {* Filter on storedPayments and default payment methods (SW 5 needs internally array<int, array> for $sPayments) *}
    {assign var="paymentMethods" value=[]}
    {assign var="storedPaymentMethods" value=[]}
    {foreach $sPayments as $paymentMethod}
        {if 'isStoredPayment'|array_key_exists:$paymentMethod && true === $paymentMethod.isStoredPayment}
            {$storedPaymentMethods[] = $paymentMethod}
        {else}
            {$paymentMethods[] = $paymentMethod}
        {/if}
    {/foreach}

    {include file="frontend/checkout/adyen_configuration.tpl"}

    {block name='frontend_checkout_payment_content_adyen_stored_payment_methods'}
        {if !empty($storedPaymentMethods)}
            {if !empty($paymentMethods)}
                <h4 class="payment--method-headline panel--title is--underline adyen-method-section-title">
                    {s namespace='adyen/checkout/payment' name='storedPaymentMethodTitle'}{/s}
                </h4>
            {/if}
            {assign var=sPayments value=$storedPaymentMethods}
            {$smarty.block.parent}
        {/if}
    {/block}

    {block name='frontend_checkout_payment_content_adyen_payment_methods'}
        {if !empty($paymentMethods)}
            {if !empty($storedPaymentMethods)}
                <h4 class="payment--method-headline panel--title is--underline adyen-method-section-title">
                    {s namespace='adyen/checkout/payment' name='paymentMethodTitle'}{/s}
                </h4>
            {/if}
            {assign var=sPayments value=$paymentMethods}
            {$smarty.block.parent}
        {/if}
    {/block}
{/block}

{block name='frontend_checkout_payment_fieldset_input_label'}
    {if $payment_mean.image}
        <div class="method--image">
            <img src="{$payment_mean.image}" alt="{$payment_mean.description}"/>
        </div>
        {$smarty.block.parent}
    {* Plugin compatibility SwagPaymentPayPalUnified *}
    {elseif $payment_mean.name|strstr:"SwagPaymentPayPalUnified"}
        {include file="string:{$payment_mean.additionaldescription|unescape:'html'}"}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Method Description *}
{block name='frontend_checkout_payment_fieldset_description'}
    {if $payment_mean.name|strstr:"SwagPaymentPayPal"}
        <div class="method--description is--last">
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
