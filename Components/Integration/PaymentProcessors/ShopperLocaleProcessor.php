<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\ShopperLocaleProcessor as BaseShopperLocaleProcessor;

/**
 * Class ShopperLocaleProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperLocaleProcessor implements BaseShopperLocaleProcessor
{
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $builder->setShopperLocale(Shopware()->Shop()->getLocale()->getLocale());
    }
}
