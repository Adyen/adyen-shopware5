{extends file='parent:frontend/checkout/cart.tpl'}

{block name="frontend_index_after_body"}
    {$smarty.block.parent}

    {if $adyenShowExpressCheckout }
        {include file="frontend/checkout/adyen_libaries.tpl"}
        <script src="https://pay.google.com/gp/p/js/pay.js"></script>
    {/if}

{/block}

{* Spike test Express Checkout integration *}
{block name='frontend_checkout_cart_table_actions_bottom'}
    {$smarty.block.parent}

    {if $adyenShowExpressCheckout }
        {block name='adyen_frontend_detail_express_buy_button'}
            <div class="table--actions">
                <div class="main--actions">
                    <div class="btn--checkout-proceed right">
                        {assign var="adyenExpressCheckoutPaymentTypes" value=['applepay', 'amazonpay', 'paywithgoogle', 'paypal']}
                        {foreach $adyenExpressCheckoutPaymentTypes as $adyenPaymentMethodType}
                            <form data-adyen-express-checkout-form method="post" action="{url controller=AdyenExpressCheckout action=finish}" class="buybox--form">
                                <input type="hidden" name="adyen_payment_method" value="{$adyenPaymentMethodType}"/>
                                <input type="hidden" name="adyenExpressPaymentMethodStateData">
                                <input type="hidden" name="adyenShippingAddress">
                                <input type="hidden" name="adyenBillingAddress">
                                <input type="hidden" name="adyenEmail">

                                <div
                                        data-checkoutConfigUrl="{url
                                        module='frontend'
                                        controller='AdyenExpressCheckout'
                                        action='getCheckoutConfig'
                                        }"
                                        data-additionalDataUrl="{url module='frontend' controller='AdyenPaymentProcess' action='handleAdditionalData'}"
                                        data-adyenPaymentMethodType="{$adyenPaymentMethodType}"
                                        data-adyen-express-checkout>
                                </div>
                            </form>
                        {/foreach}
                    </div>
                </div>
            </div>
        {/block}
    {/if}
{/block}
