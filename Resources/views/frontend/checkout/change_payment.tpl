{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name='frontend_checkout_payment_content'}
    {include file="frontend/checkout/adyen_libaries.tpl"}

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
            <div class="panel--body is--wide block-group">
                {foreach $sPayments as $payment_mean}
                    <div class="payment--method block{if $payment_mean@last} method_last{else} method{/if} payment-{$payment_mean.name}">

                        {* Radio Button *}
                        {block name='frontend_checkout_payment_fieldset_input_radio'}
                            <div class="method--input">
                                <input type="radio" name="payment" class="radio auto_submit" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}"{if $payment_mean.id eq $sFormData.payment or (!$sFormData && !$smarty.foreach.register_payment_mean.index)} checked="checked"{/if} />
                            </div>
                        {/block}

                        {* Method Name *}
                        {block name='frontend_checkout_payment_fieldset_input_label'}
                            <div class="method--label is--first">
                                <label class="method--name is--strong" for="payment_mean{$payment_mean.id}">{$payment_mean.description}</label>
                            </div>
                        {/block}

                        {* Method Description *}
                        {block name='frontend_checkout_payment_fieldset_description'}
                            <div class="method--description is--last">
                                {include file="string:{$payment_mean.additionaldescription}"}
                            </div>
                        {/block}

                        {* Method Logo *}
                        {block name='frontend_checkout_payment_fieldset_template'}
                            <div class="payment--method-logo payment_logo_{$payment_mean.name}"></div>
                            {if "frontend/plugins/payment/`$payment_mean.template`"|template_exists}
                                <div class="method--bankdata{if $payment_mean.id != $form_data.payment} is--hidden{/if}">
                                    {include file="frontend/plugins/payment/`$payment_mean.template`" form_data=$sFormData error_flags=$sErrorFlag payment_means=$sPayments}
                                </div>
                            {/if}
                        {/block}
                    </div>
                {/foreach}
            </div>
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
            <div class="panel--body is--wide block-group">
                {foreach $sPayments as $payment_mean}
                    <div class="payment--method block{if $payment_mean@last} method_last{else} method{/if} payment-{$payment_mean.name}">

                        {* Radio Button *}
                        {block name='frontend_checkout_payment_fieldset_input_radio'}
                            <div class="method--input">
                                <input type="radio" name="payment" class="radio auto_submit" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}"{if $payment_mean.id eq $sFormData.payment or (!$sFormData && !$smarty.foreach.register_payment_mean.index)} checked="checked"{/if} />
                            </div>
                        {/block}

                        {* Method Name *}
                        {block name='frontend_checkout_payment_fieldset_input_label'}
                            <div class="method--label is--first">
                                <label class="method--name is--strong" for="payment_mean{$payment_mean.id}">{$payment_mean.description}</label>
                            </div>
                        {/block}

                        {* Method Description *}
                        {block name='frontend_checkout_payment_fieldset_description'}
                            <div class="method--description is--last">
                                {include file="string:{$payment_mean.additionaldescription}"}
                            </div>
                        {/block}

                        {* Method Logo *}
                        {block name='frontend_checkout_payment_fieldset_template'}
                            <div class="payment--method-logo payment_logo_{$payment_mean.name}"></div>
                            {if "frontend/plugins/payment/`$payment_mean.template`"|template_exists}
                                <div class="method--bankdata{if $payment_mean.id != $form_data.payment} is--hidden{/if}">
                                    {include file="frontend/plugins/payment/`$payment_mean.template`" form_data=$sFormData error_flags=$sErrorFlag payment_means=$sPayments}
                                </div>
                            {/if}
                        {/block}
                    </div>
                {/foreach}
            </div>
        {/if}
    {/block}

{/block}

{block name='frontend_checkout_payment_fieldset_input_label'}
    {if $payment_mean.image}
        <div class="method--image">
            <img src="{$payment_mean.image}" alt="{$payment_mean.description}"/>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
