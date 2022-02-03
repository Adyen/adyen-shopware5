{extends file='parent:frontend/register/payment_fieldset.tpl'}

{block name="frontend_register_payment_fieldset_input_radio"}
    {assign var='isStoredPayment' value=('isStoredPayment'|array_key_exists:$payment_mean && true === $payment_mean.isStoredPayment)}
    {if $isStoredPayment}
        {append var="payment_mean" value=($payment_mean.stored_method_umbrella_id) index='id'}
    {/if}
    <input
        type="radio"
        name="register[payment]"
        value="{$payment_mean.id}"
        id="payment_mean{$payment_mean.id}"
        {if ($payment_mean.id eq $form_data.payment
            or (!$form_data and !$payment_mean@index)
            or ($isStoredPayment and $adyenUserPreference and $payment_mean.stored_method_id === $adyenUserPreference.storedMethodId)
        )} checked="checked"{/if}
        />
{/block}
