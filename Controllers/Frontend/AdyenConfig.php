<?php

declare(strict_types=1);

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProvider;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\DataConversion;
use AdyenPayment\Serializer\PaymentMeanCollectionSerializer;
use AdyenPayment\Shopware\Serializer\SwPaymentMeanCollectionSerializer;
use AdyenPayment\Utils\JsonUtil;

class Shopware_Controllers_Frontend_AdyenConfig extends Shopware_Controllers_Frontend_Checkout
{
    /** @var DataConversion */
    private $dataConversion;

    /** @var Configuration */
    private $configuration;

    /** @var EnrichedPaymentMeanProviderInterface */
    private $enrichedPaymentMeanProvider;

    /** @var PaymentMeanCollectionSerializer */
    private $paymentMeanCollectionSerializer;

    /** @var Shopware_Components_Modules */
    private $modules;

    public function preDispatch(): void
    {
        $this->configuration = $this->get(Configuration::class);
        $this->dataConversion = $this->get(DataConversion::class);
        $this->enrichedPaymentMeanProvider = $this->get(EnrichedPaymentMeanProvider::class);
        $this->paymentMeanCollectionSerializer = $this->get(SwPaymentMeanCollectionSerializer::class);
        $this->modules = $this->get('modules');
    }

    public function indexAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        try {
            $admin = $this->modules->Admin();

            $enrichedPaymentMethods = ($this->enrichedPaymentMeanProvider)(
                PaymentMeanCollection::createFromShopwareArray($admin->sGetPaymentMeans())
            );

            $sBasket = $this->getBasket();

            $shop = Shopware()->Shop();
            $orderCurrency = $shop->getCurrency();

            $adyenConfig = [
                'status' => 'success',
                'shopLocale' => $this->dataConversion->getISO3166FromLocale($shop->getLocale()->getLocale()),
                'clientKey' => $this->configuration->getClientKey($shop),
                'environment' => $this->configuration->getEnvironment($shop),
                'enrichedPaymentMethods' => ($this->paymentMeanCollectionSerializer)($enrichedPaymentMethods),
                'adyenOrderTotal' => round($sBasket['sAmount'], 2),
                'adyenOrderCurrency' => $sBasket['sCurrencyName'] ?? $orderCurrency
            ];

            $this->Response()->setBody(
                JsonUtil::encode($adyenConfig)
            );
        } catch (Exception $exception) {
            $this->Response()->setBody(
                json_encode([
                    'status' => 'error',
                    'content' => $exception->getMessage()
                ])
            );
        }

    }
}
