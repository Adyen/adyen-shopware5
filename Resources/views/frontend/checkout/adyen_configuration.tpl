{if $sAdyenConfig}
    <div data-shopLocale='{$sAdyenConfig.shopLocale}'
         data-adyenClientKey='{$sAdyenConfig.clientKey}'
         data-adyenEnvironment='{$sAdyenConfig.environment}'
         data-enrichedPaymentMethods='{$sPayments|@json_encode}'
         data-resetSessionUrl='{url controller="Adyen" action="ResetValidPaymentSession"}'
         {if $mAdyenSnippets}data-adyensnippets="{$mAdyenSnippets}"{/if}
         class="adyen-payment-selection adyen-config">
    </div>
{/if}
