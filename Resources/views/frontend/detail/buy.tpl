{extends file='parent:frontend/detail/buy.tpl'}

{* Express Checkout buttons *}
{block name='frontend_detail_buy'}
    {$smarty.block.parent}

    {if $adyenShowExpressCheckout && (!$sArticle.sConfigurator || ($sArticle.sConfigurator && $activeConfiguratorSelection)) }
        {block name='adyen_frontend_detail_express_buy_button'}
            {assign var="adyenExpressCheckoutPaymentTypes" value=['applepay', 'amazonpay', 'paywithgoogle', 'paypal']}
            {foreach $adyenExpressCheckoutPaymentTypes as $adyenPpaymentMethodType}
                <form data-adyen-express-checkout-form method="post" action="{url controller=AdyenExpressCheckout action=finish}" class="buybox--form">
                    <input type="hidden" name="adyen_payment_method" value="{$adyenPpaymentMethodType}"/>
                    <input type="hidden" name="adyen_article_number" value="{$sArticle.ordernumber}"/>
                    <input type="hidden" name="adyenExpressPaymentMethodStateData">
                    <input type="hidden" name="adyenShippingAddress">

                    <div
                            data-checkoutConfigUrl="{url
                            module='frontend'
                            controller='AdyenExpressCheckout'
                            action='getCheckoutConfig'
                            adyen_article_number="{$sArticle.ordernumber}"
                            }"
                            data-additionalDataUrl="{url module='frontend' controller='AdyenPaymentProcess' action='handleAdditionalData'}"
                            data-adyenPaymentMethodType="{$adyenPpaymentMethodType}"
                            data-adyen-express-checkout>
                    </div>
                </form>
            {/foreach}
        {/block}
    {/if}
{/block}
