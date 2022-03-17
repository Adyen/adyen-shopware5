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

{block name="frontend_register_payment_fieldset_description"}
    {$smarty.block.parent}

    {block name="frontend_register_payment_stored_method_action_disable"}
        {if $isStoredPayment }
            <div class="block is--align-right">
                <button type="button"
                    data-adyenDisableTokenUrl="{url module='frontend' controller='disableRecurringToken' action='disabled'}"
                    data-adyen-disable-payment="{$payment_mean.stored_method_id}"
                    title="{s name="storedMethodActionDisableText"}Disable{/s}" class="btn is--warning is--right">
                    {s name="storedMethodActionDisableText"}Disable{/s}
                </button>
            </div>
        {/if}
    {/block}
{/block}
