<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ShopperReference;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\ShopperReferenceProcessor as BaseShopperReferenceProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperReferenceProcessor as PaymentLinkShopperReferenceProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Exception;

/**
 * Class ShopperReferenceProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperReferenceProcessor implements BaseShopperReferenceProcessor, PaymentLinkShopperReferenceProcessorInterface
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
        $user = $context->getCheckoutSession()->get('user');

        if (empty($user) || !isset($user['additional']['user']['id'])) {
            return;
        }

        $shop = Shopware()->Shop();

        $builder->setShopperReference(
            ShopperReference::parse(
                $shop->getHost() . '_' . $shop->getId() . '_' . $user['additional']['user']['id']
            )
        );
    }

    /**
     * @param PaymentLinkRequestBuilder $builder
     * @param PaymentLinkRequestContext $context
     *
     * @return void
     * 
     * @throws Exception
     */
    public function processPaymentLink(PaymentLinkRequestBuilder $builder, PaymentLinkRequestContext $context): void
    {
        $order = $this->orderRepository->getOrderByTemporaryId($context->getReference());

        if (!$order) {
            return;
        }

        $storeId = StoreContext::getInstance()->getStoreId();
        $container = Shopware()->Container();
        $shopRepository = $container->get('shopware_storefront.shop_gateway_dbal');
        $shop = $shopRepository->get($storeId);

        $builder->setShopperReference(
            ShopperReference::parse(
                $shop->getHost() . '_' . $shop->getId() . '_' . $order->getCustomer()->getId()
            )
        );
    }
}
