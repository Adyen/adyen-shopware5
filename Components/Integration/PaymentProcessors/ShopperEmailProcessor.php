<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\ShopperEmailProcessor as BaseShopperEmailProcessor;

/**
 * Class ShopperEmailProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperEmailProcessor implements BaseShopperEmailProcessor
{
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $stateDataEmail = $context->getStateData()->get('shopperEmail');

        if (!empty($stateDataEmail)) {
            return;
        }

        $user = $context->getCheckoutSession()->get('user');

        if (empty($user) || !isset($user['additional']['user']['email'])) {
            return;
        }

        $builder->setShopperEmail($user['additional']['user']['email']);
    }
}
