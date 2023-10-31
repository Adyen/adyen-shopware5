<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\ShopperEmailProcessor as BaseShopperEmailProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperEmailProcessor as PaymentLinkShopperEmailProcessorInterface;
use AdyenPayment\Repositories\Wrapper\OrderRepository;

/**
 * Class ShopperEmailProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperEmailProcessor implements BaseShopperEmailProcessor, PaymentLinkShopperEmailProcessorInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param PaymentRequestBuilder $builder
     * @param StartTransactionRequestContext $context
     *
     * @return void
     */
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

    /**
     * @param PaymentLinkRequestBuilder $builder
     * @param PaymentLinkRequestContext $context
     *
     * @return void
     */
    public function processPaymentLink(PaymentLinkRequestBuilder $builder, PaymentLinkRequestContext $context): void
    {
        $order = $this->orderRepository->getOrderByTemporaryId($context->getReference());

        if (!$order) {
            return;
        }

        $builder->setShopperEmail($order->getCustomer()->getEmail());
    }
}
