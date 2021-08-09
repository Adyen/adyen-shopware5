<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Doctrine\Writer\PaymentMethodWriterInterface;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMethodType;
use Doctrine\Common\Persistence\ObjectRepository;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;

class EnrichedPaymentMeanProvider implements EnrichedPaymentMeanProviderInterface
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

    public function __invoke(array $shopwareMethods): array
    {
        $paymentMeans = PaymentMeanCollection::createFromShopwareArray($shopwareMethods);
        $shopwareMethods = $paymentMeans->filterByAdyenSource();

        $paymentMethodOptions = $this->paymentMethodOptionsBuilder->__invoke();
        if (0 === $paymentMethodOptions['value']) {
            return $shopwareMethods->toShopwareArray();
        }

        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
            $this->paymentMethodService->getPaymentMethods(
                $paymentMethodOptions['countryCode'],
                $paymentMethodOptions['currency'],
                $paymentMethodOptions['value']
            )
        );

        $storedPaymentMethods = $adyenPaymentMethods->filterByPaymentType(PaymentMethodType::stored());
        $this->saveStoredPaymentMethods($storedPaymentMethods);

        return $shopwareMethods->enrichAdyenPaymentMeans($adyenPaymentMethods);
    }

    // TODO: refactor to appropriate class
    private function saveStoredPaymentMethods(PaymentMethodCollection $storedPaymentMethods)
    {
        // Detached shop cannot be saved with ORM relation mapping
        // the actual shop entity needs to be fetched.
        $shopId = Shopware()->Shop() ? Shopware()->Shop()->getId() : 0;
        if (0 === $shopId) {
            return;
        }

        $shop = $this->shopRepository->find($shopId);
        if (null === $shop) {
            return;
        }

        foreach ($storedPaymentMethods as $storedPaymentMethod) {
            $this->paymentMethodWriter->__invoke($storedPaymentMethod, $shop);
        }
    }
}
