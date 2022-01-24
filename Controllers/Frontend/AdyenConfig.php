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

class Shopware_Controllers_Frontend_AdyenConfig extends Enlight_Controller_Action
{
    private DataConversion $dataConversion;
    private Configuration $configuration;
    private EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider;
    private PaymentMeanCollectionSerializer $paymentMeanCollectionSerializer;

    public function preDispatch(): void
    {
        $this->configuration = $this->get(Configuration::class);
        $this->dataConversion = $this->get(DataConversion::class);
        $this->enrichedPaymentMeanProvider = $this->get(EnrichedPaymentMeanProvider::class);
        $this->paymentMeanCollectionSerializer = $this->get(SwPaymentMeanCollectionSerializer::class);
    }

    public function indexAction(\Enlight_Controller_Action $subject): void
    {
        try {
            $this->Front()->Plugins()->ViewRenderer()->setNoRender();

            $admin = Shopware()->Modules()->Admin();
            $enrichedPaymentMethods = ($this->enrichedPaymentMeanProvider)(
                PaymentMeanCollection::createFromShopwareArray($admin->sGetPaymentMeans())
            );

            $shop = Shopware()->Shop();

            $adyenConfig = [
                'status' => 'success',
                'shopLocale' => $this->dataConversion->getISO3166FromLocale($shop->getLocale()->getLocale()),
                'clientKey' => $this->configuration->getClientKey($shop),
                'environment' => $this->configuration->getEnvironment($shop),
                'enrichedPaymentMethods' => ($this->paymentMeanCollectionSerializer)($enrichedPaymentMethods),
            ];

            $this->Response()->setHeader('Content-Type', 'application/json');
            $this->Response()->setBody(
                json_encode($adyenConfig, JSON_THROW_ON_ERROR)
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
