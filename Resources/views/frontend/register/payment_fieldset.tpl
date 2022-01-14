{extends file='parent:frontend/register/payment_fieldset.tpl'}

{block name="frontend_register_payment_fieldset_input_radio"}
    {assign var='isStoredPayment' value=('isStoredPayment'|array_key_exists:$payment_mean && true === $payment_mean.isStoredPayment)}
    {if not $isStoredPayment}
        <input type="radio" name="register[payment]" value="{$payment_mean.id}" id="payment_mean{$payment_mean.id}"{if $payment_mean.id eq $form_data.payment or (!$form_data && !$payment_mean@index)} checked="checked"{/if} />
    {/if}
{/block}

{block name="frontend_register_payment_fieldset_input_label"}
    {assign var='isStoredPayment' value=('isStoredPayment'|array_key_exists:$payment_mean && true === $payment_mean.isStoredPayment)}
    <label{if not $isStoredPayment} for="payment_mean{$payment_mean.id}"{/if} class="is--strong">
        {$payment_mean.description}
    </label>
{/block}
