<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ShopperName;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\ShopperNameProcessor as BaseShopperNameProcessor;

/**
 * Class ShopperNameProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperNameProcessor implements BaseShopperNameProcessor
{
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $rawShopperName = $context->getStateData()->get('shopperName');

        if (!empty($rawShopperName)) {
            return;
        }

        $user = $context->getCheckoutSession()->get('user');

        if (empty($user) || !isset($user['additional']['user'])) {
            return;
        }

        $shopperName = new ShopperName(
            $user['additional']['user']['firstname'] ?? '',
            $user['additional']['user']['lastname'] ?? ''
        );

        $builder->setShopperName($shopperName);
    }
}
