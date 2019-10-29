{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_form'}
    {include file="frontend/checkout/adyen_libaries.tpl"}

    {$smarty.block.parent}
{/block}

{block name='frontend_checkout_confirm_error_messages'}
    <div data-adyen-checkout-error="true"></div>
    {$smarty.block.parent}
{/block}