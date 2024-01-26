<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ShopperName;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\ShopperNameProcessor as BaseShopperNameProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperNameProcessor as PaymentLinkShopperNameProcessorInterface;
use AdyenPayment\Repositories\Wrapper\OrderRepository;

/**
 * Class ShopperNameProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperNameProcessor implements BaseShopperNameProcessor, PaymentLinkShopperNameProcessorInterface
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
        $shopperName = new ShopperName(
            $order->getCustomer()->getFirstname() ?? '',
            $order->getCustomer()->getLastname() ?? ''
        );

        $builder->setShopperName($shopperName);
    }
}
