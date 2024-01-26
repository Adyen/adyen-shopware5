<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\BirthdayProcessor as BaseBirthdayProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperBirthdayProcessor as PaymentLinkShopperBirthdayProcessorInterface;
use AdyenPayment\Repositories\Wrapper\OrderRepository;

/**
 * Class BirthdayProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class BirthdayProcessor implements BaseBirthdayProcessor, PaymentLinkShopperBirthdayProcessorInterface
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
        $stateDataBirthday = $context->getStateData()->get('dateOfBirth');

        if (!empty($stateDataBirthday)) {
            return;
        }

        $user = $context->getCheckoutSession()->get('user');

        if (empty($user) || !isset($user['additional']['user']['birthday'])) {
            return;
        }

        $builder->setDateOfBirth($user['additional']['user']['birthday']);
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

        $birthday = $order->getCustomer()->getBirthday();

        if (!$birthday) {
            return;
        }

        $builder->setDateOfBirth($birthday->format('Y-m-d'));
    }
}
