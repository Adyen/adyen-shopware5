<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Shopware\Provider\PaymentMeansProviderInterface;
use Enlight_Controller_Request_Request;

final class StoredPaymentMeanProvider implements StoredPaymentMeanProviderInterface
{
    private EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider;
    private PaymentMeansProviderInterface $paymentMeansProvider;

    public function __construct(
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider,
        PaymentMeansProviderInterface $paymentMeansProvider
    ) {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
        $this->paymentMeansProvider = $paymentMeansProvider;
    }

    public function fromRequest(Enlight_Controller_Request_Request $request): ?PaymentMean
    {
        $registerPayment = $request->getParam('register', [])['payment'] ?? null;
        if (null === $registerPayment) {
            return null;
        }

        $enrichedPaymentMeans = ($this->enrichedPaymentMeanProvider)(
            PaymentMeanCollection::createFromShopwareArray(($this->paymentMeansProvider)())
        );

        return $enrichedPaymentMeans->fetchByUmbrellaStoredMethodId($registerPayment);
    }
}
