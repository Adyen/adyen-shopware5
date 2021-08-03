<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\AdyenPayment;
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
        $shopwareMethods = array_filter($shopwareMethods, function ($method) {
            $source = (int) ($method['source'] ?? null);
            return SourceType::load($source)->equals(SourceType::adyen());
        });

        $paymentMethodOptions = $this->paymentMethodOptionsBuilder->__invoke();
        if ($paymentMethodOptions['value'] == 0) {
            return $shopwareMethods;
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

        $paymentMethodEnricher = $this->paymentMethodEnricher;

        // TODO: refactor to a collection or more clean structure
        $shopwareMethods = array_filter(array_map(static function (array $shopwareMethod) use (
            $adyenPaymentMethods,
            $paymentMethodEnricher
        ) {
            $source = (int)($shopwareMethod['source'] ?? null);
            if (!SourceType::load($source)->equals(SourceType::adyen())) {
                return $shopwareMethod;
            }

            /** @var Attribute $attribute */
            $attribute = $shopwareMethod['attribute'];
            $typeOrId = $attribute->get(AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID)
                ?: $attribute->get(AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL);

            $paymentMethod = $adyenPaymentMethods->fetchByTypeOrId($typeOrId);
            if (!$paymentMethod) {
                return [];
            }

            return $paymentMethodEnricher->enrichPaymentMethod($shopwareMethod, $paymentMethod);
        }, $shopwareMethods));

        return $shopwareMethods;
    }

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
