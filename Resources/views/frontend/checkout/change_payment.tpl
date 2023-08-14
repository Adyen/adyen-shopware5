{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    {assign var="paymentMethods" value=[]}
    {assign var="storedPaymentMethods" value=[]}
    {foreach $sPayments as $paymentMethod}
        {if $paymentMethod.isStoredPaymentMethod}
            {append var="paymentMethod" value="{$paymentMethod.id}" index='originalId'}
            {append var="paymentMethod" value="{$paymentMethod.id}_{$paymentMethod.storedPaymentMethodId}" index='id'}
            {$storedPaymentMethods[] = $paymentMethod}
        {else}
            {append var="paymentMethod" value="{$paymentMethod.id}" index='originalId'}
            {$paymentMethods[] = $paymentMethod}
        {/if}
    {/foreach}

    {block name='frontend_checkout_payment_content_adyen_stored_payment_methods'}
        {if !empty($storedPaymentMethods)}
            <div id="stored_payment_wrapper">
                {if !empty($paymentMethods)}
                    <h4 class="payment--method-headline panel--title is--underline adyen-method-section-title">
                        {s namespace='frontend/adyen/checkout' name='payment/adyen/stored_payment_methods_title'}Stored payment methods{/s}
                    </h4>
                {/if}
                {assign var=sPayments value=$storedPaymentMethods}
                {$smarty.block.parent}
            </div>
        {/if}
    {/block}

    {block name='frontend_checkout_payment_content_adyen_payment_methods'}
        {if !empty($paymentMethods)}
            {if !empty($storedPaymentMethods)}
                <h4 class="payment--method-headline panel--title is--underline adyen-method-section-title">
                    {s namespace='frontend/adyen/checkout' name='payment/adyen/payment_methods_title'}Payment methods{/s}
                </h4>
            {/if}
            {assign var=sPayments value=$paymentMethods}
            {$smarty.block.parent}

            <input type="hidden" name="adyenStoredPaymentMethodId">
        {/if}
    {/block}
{/block}

{block name='frontend_checkout_payment_fieldset_input_label'}
    {if $payment_mean.surchargeAmount}
        {append var="payment_mean" value="{$payment_mean.description} (+ {$payment_mean.surchargeAmount|currency})" index='description'}
    {/if}
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

{block name='frontend_checkout_payment_fieldset_input_radio'}
    {if $payment_mean.isAdyenPaymentMethod}
    <div class="method--input">
        <input
                type="radio" name="payment" class="radio auto_submit" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}"
                data-adyen-payment-method="true" data-adyen-payment-method-type="{$payment_mean.adyenPaymentType}"
                data-adyen-stored_payment_method_id="{$payment_mean.storedPaymentMethodId}"
                data-adyen-payment-mean-id="{$payment_mean.originalId}"
                {if $payment_mean.id eq $sFormData.payment or (!$sFormData && !$smarty.foreach.register_payment_mean.index)} checked="checked"{/if}
        />
    </div>
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
