<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ApplicationInfo\ApplicationInfo;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ApplicationInfo\ExternalPlatform;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\ApplicationInfoProcessor as ApplicationInfoProcessorInterface;
use AdyenPayment\Components\ShopwareVersionCheck;
use Exception;
use Shopware\Models\Plugin\Plugin;

/**
 * Class ApplicationInfoProcessor.
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class ApplicationInfoProcessor implements ApplicationInfoProcessorInterface
{
    /**
     * @param PaymentRequestBuilder $builder
     * @param StartTransactionRequestContext $context
     *
     * @return void
     *
     * @throws Exception
     */
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $shopName = Shopware()->Shop()->getName();
        $shopVersion = $this->getShopVersion();
        $pluginVersion = $this->getPluginVersion();

        $shopName && $builder->setApplicationInfo(
            new ApplicationInfo(new ExternalPlatform($shopName, $shopVersion), $pluginVersion)
        );
    }

    /**
     * Returns Shopware5 version.
     *
     * @return string
     */
    private function getShopVersion(): string
    {
        /** @var ShopwareVersionCheck $versionCheck */
        $versionCheck = Shopware()->Container()->get('adyen_payment.components.shopware_version_check');

        return $versionCheck->getShopwareVersion();
    }

    /**
     * Returns Adyen plugin version.
     *
     * @return string
     */
    private function getPluginVersion(): string
    {
        $pluginManager = Shopware()->Container()->get('shopware.plugin_manager');
        /** @var Plugin $pluginInfo */
        $pluginInfo = $pluginManager->getPluginByName('AdyenPayment');

        return $pluginInfo->getVersion();
    }
}
