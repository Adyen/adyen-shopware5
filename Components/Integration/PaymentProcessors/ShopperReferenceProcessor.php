<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ShopperReference;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\ShopperReferenceProcessor as BaseShopperReferenceProcessor;

/**
 * Class ShopperReferenceProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperReferenceProcessor implements BaseShopperReferenceProcessor
{
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $user = $context->getCheckoutSession()->get('user');

        if (empty($user) || !isset($user['additional']['user']['id'])) {
            return;
        }

        $shop = Shopware()->Shop();

        $builder->setShopperReference(ShopperReference::parse(
            $shop->getHost() . '_' . $shop->getId() . '_' . $user['additional']['user']['id']
        ));
    }
}
