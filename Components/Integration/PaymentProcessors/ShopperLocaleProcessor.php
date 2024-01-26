<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\ShopperLocaleProcessor as BaseShopperLocaleProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperLocaleProcessor as PaymentLinkShopperLocaleProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;

/**
 * Class ShopperLocaleProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ShopperLocaleProcessor implements BaseShopperLocaleProcessor, PaymentLinkShopperLocaleProcessorInterface
{
    /**
     * @param PaymentRequestBuilder $builder
     * @param StartTransactionRequestContext $context
     *
     * @return void
     */
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $builder->setShopperLocale(Shopware()->Shop()->getLocale()->getLocale());
    }

    /**
     * @param PaymentLinkRequestBuilder $builder
     * @param PaymentLinkRequestContext $context
     *
     * @return void
     */
    public function processPaymentLink(PaymentLinkRequestBuilder $builder, PaymentLinkRequestContext $context): void
    {
        $storeId = StoreContext::getInstance()->getStoreId();
        $container = Shopware()->Container();

        $shopRepository = $container->get('shopware_storefront.shop_gateway_dbal');
        $shop = $shopRepository->get($storeId);

        if (!$shop->getLocale()) {
            return;
        }

        $builder->setShopperLocale($shop->getLocale()->getLocale());
    }
}
