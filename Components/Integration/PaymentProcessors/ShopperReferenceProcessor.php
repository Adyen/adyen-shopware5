<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ShopperReference;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperReferenceProcessor as PaymentLinkShopperReferenceProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use AdyenPayment\Utilities\Shop;
use Exception;

/**
 * Class ShopperReferenceProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperReferenceProcessor implements PaymentLinkShopperReferenceProcessorInterface
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
                $shop->getHost() . '_' . $order->getShop()->getId() . '_' . $order->getCustomer()->getId()
            )
        );
    }
}
