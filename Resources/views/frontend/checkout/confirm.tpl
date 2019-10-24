{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_left_payment_method"}
    {if $sUserData.additional.payment.image}
        <div class="payment--method-image">
            <img src="{$sUserData.additional.payment.image}"  alt="{$sUserData.additional.payment.description}"/>
        </div>
    {/if}

    {$smarty.block.parent}
{/block}