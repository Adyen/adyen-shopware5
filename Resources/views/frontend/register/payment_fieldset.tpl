{extends file='parent:frontend/register/payment_fieldset.tpl'}

{block name="frontend_register_payment_fieldset_input_radio"}
    {assign var='isStoredPayment' value=('isStoredPayment'|array_key_exists:$payment_mean && true === $payment_mean.isStoredPayment)}
    {assign var='shouldBeChecked' value=false}
    {if $isStoredPayment}
        {append var="payment_mean" value=($payment_mean.stored_method_umbrella_id) index='id'}
        {if $adyenUserPreference and $payment_mean.stored_method_id === $adyenUserPreference.storedMethodId}
            {assign var='shouldBeChecked' value=true}
        {/if}
    {/if}
    <input
        type="radio"
        name="register[payment]"
        value="{$payment_mean.id}"
        id="payment_mean{$payment_mean.id}"
        {if ($payment_mean.id eq $form_data.payment or (!$form_data && !$payment_mean@index) or $shouldBeChecked)} checked="checked"{/if}
        />
{/block}
