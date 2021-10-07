<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Doctrine\Writer\PaymentMethodWriterInterface;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Payment\PaymentMethodType;
use Doctrine\Persistence\ObjectRepository;

final class EnrichedPaymentMeanProvider implements EnrichedPaymentMeanProviderInterface
{
    /**
     * @var PaymentMethodService
     */
    protected $paymentMethodService;
    /**
     * @var PaymentMethodOptionsBuilderInterface
     */
    private $paymentMethodOptionsBuilder;
    /**
     * @var PaymentMethodEnricherInterface
     */
    private $paymentMethodEnricher;
    /**
     * @var PaymentMethodWriterInterface
     */
    private $paymentMethodWriter;
    /**
     * @var ObjectRepository
     */
    private $shopRepository;

    public function __construct(
        PaymentMethodService $paymentMethodService,
        PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder,
        PaymentMethodEnricherInterface $paymentMethodEnricher,
        PaymentMethodWriterInterface $paymentMethodWriter,
        ObjectRepository $shopRepository
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentMethodOptionsBuilder = $paymentMethodOptionsBuilder;
        $this->paymentMethodEnricher = $paymentMethodEnricher;
        $this->paymentMethodWriter = $paymentMethodWriter;
        $this->shopRepository = $shopRepository;
    }

    public function __invoke(PaymentMeanCollection $paymentMeans): PaymentMeanCollection
    {
        $adyenShopwareMethods = $paymentMeans->filterByAdyenSource();

        $paymentMethodOptions = ($this->paymentMethodOptionsBuilder)();
        if (0 === $paymentMethodOptions['value']) {
            return $adyenShopwareMethods;
        }

        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
            $this->paymentMethodService->getPaymentMethods(
                $paymentMethodOptions['countryCode'],
                $paymentMethodOptions['currency'],
                $paymentMethodOptions['value']
            )
        );

        return $adyenShopwareMethods->enrichAdyenPaymentMeans($adyenPaymentMethods, $this->paymentMethodEnricher);
    }
}
