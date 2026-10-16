{extends file='parent:frontend/detail/index.tpl'}

{block name="frontend_index_after_body"}
    {$smarty.block.parent}

    {if $adyenShowExpressCheckout }
        {include file="frontend/checkout/adyen_libaries.tpl"}
    {/if}

{/block}
