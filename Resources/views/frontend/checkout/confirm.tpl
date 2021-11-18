{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_form'}
    {include file="frontend/checkout/adyen_libaries.tpl"}

    {$smarty.block.parent}
{/block}

{block name="frontend_checkout_confirm_left_payment_method"}
    {if $sUserData.additional.payment.image}
        <div class="payment--method-image">
            <img src="{$sUserData.additional.payment.image}"  alt="{$sUserData.additional.payment.description}"/>
        </div>
    {/if}

    {$smarty.block.parent}
{/block}

{block name='frontend_index_body_attributes'}
    {assign var=adyenType value=$sUserData.additional.payment.attribute->get('adyen_type')}

    {$smarty.block.parent}
    data-adyenAjaxDoPaymentUrl="{url module='frontend' controller='adyen' action='ajaxDoPayment'}"
    data-adyenAjaxPaymentDetails="{url module='frontend' controller='adyen' action='paymentDetails'}"
    data-adyenAjaxThreeDsUrl="{url module='frontend' controller='adyen' action='ajaxThreeDs'}"
    {if $mAdyenSnippets}
        data-adyenSnippets="{$mAdyenSnippets}"
    {/if}
    {if $adyenType}data-adyenType="{$adyenType}"{/if}
    {if $sAdyenGoogleConfig}
        data-adyenGoogleConfig='{$sAdyenGoogleConfig}'
    {/if}
    {if $adyenPaymentState}
        data-adyenPaymentState='{$adyenPaymentState}'
    {/if}
    {if $sUserData.additional.payment.source == $adyenSourceType}
        data-adyenIsAdyenPayment='true'
    {/if}
{/block}

{block name='frontend_checkout_confirm_payment_method_panel'}
    {$smarty.block.parent}
    {include file="frontend/checkout/adyen_configuration.tpl"}
{/block}


{block name='frontend_checkout_confirm_error_messages'}
    <div data-adyen-checkout-error="true">
        {if $sErrorMessages}{include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}{/if}
    </div>
    {$smarty.block.parent}
{/block}
