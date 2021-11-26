{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name="frontend_index_content"}
    <div id="shipping_payment_wrapper" class="adyen-hidden--all">
        {$smarty.block.parent}
    </div>
{/block}
