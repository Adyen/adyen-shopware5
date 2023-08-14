{extends file='parent:frontend/register/payment_fieldset.tpl'}

{block name="frontend_register_payment_fieldset_input_radio"}
    {if $payment_mean.isStoredPaymentMethod}
        {append var="payment_mean" value="{$payment_mean.id}_{$payment_mean.storedPaymentMethodId}" index='id'}
    {/if}
    <input
        type="radio"
        name="register[payment]"
        value="{$payment_mean.id}"
        id="payment_mean{$payment_mean.id}"
        {if ($payment_mean.id eq $form_data.payment
            or (!$form_data and !$payment_mean@index)
            or ($payment_mean.isStoredPaymentMethod and $adyenUserPreference and $payment_mean.storedPaymentMethodId === $adyenUserPreference.storedMethodId)
        )} checked="checked"{/if}
        />
{/block}

{block name="frontend_register_payment_fieldset_description"}
    {$smarty.block.parent}

    {block name="frontend_register_payment_stored_method_action_disable"}
        {if $payment_mean.isStoredPaymentMethod }
            <div class="block is--align-right">
                <button type="button"
                    data-adyen-disable-payment
                    data-adyenDisableTokenUrl="{url module='frontend' controller='AdyenPaymentProcess' action='disableCardDetails'}"
                    data-adyenStoredMethodId="{$payment_mean.storedPaymentMethodId}"
                    title="{s name="payment/adyen/disable"}Disable{/s}" class="btn is--warning is--right">
                    {s name="payment/adyen/disable"}Disable{/s}
                </button>
            </div>
        {/if}
    {/block}
{/block}

{block name="frontend_register_payment_fieldset"}
    {$smarty.block.parent}
    <div class="adyenDisableTokenConfirmationModal adyen-hidden--all">
        <div class="block is--align-center">
            <h2>{s name='payment/adyen/disable_confirm_message'}Are you sure to remove the stored payment method?{/s}</h2>
            <div class="buttons-container">
                <button type="button" class="disableConfirm btn is--primary left">
                    {s name='payment/adyen/confirm'}Confirm{/s}
                </button>
                <button type="button" class="disableCancel btn is--secondary right">
                    {s name='payment/adyen/cancel'}Cancel{/s}
                </button>
            </div>
            <div class="modal-error-container"></div>
        </div>
    </div>
{/block}
