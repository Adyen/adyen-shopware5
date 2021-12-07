<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Components\Adyen\Mapper\PaymentMethodMapperInterface;
use AdyenPayment\Components\Adyen\PaymentMethod\PaymentMethodsProviderInterface;
use AdyenPayment\Dbal\Writer\Payment\PaymentMeansSubShopsWriterInterface;
use AdyenPayment\Doctrine\Writer\PaymentMethodWriterInterface;
use AdyenPayment\Models\Enum\PaymentMethod\ImportStatus;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRuleInterface;
use Doctrine\Persistence\ObjectRepository;
use Shopware\Models\Shop\Shop;

final class PaymentMethodImporter implements PaymentMethodImporterInterface
{
    private PaymentMethodsProviderInterface $paymentMethodsProvider;
    private ObjectRepository $shopRepository;
    private UsedFallbackConfigRuleInterface $usedFallbackConfigRule;
    private PaymentMethodMapperInterface $paymentMethodMapper;
    private PaymentMethodWriterInterface $paymentMethodWriter;
    private PaymentMeansSubShopsWriterInterface $paymentMeansSubShopsWriter;

    public function __construct(
        PaymentMethodsProviderInterface $paymentMethodsProvider,
        ObjectRepository $shopRepository,
        UsedFallbackConfigRuleInterface $usedFallbackConfigRule,
        PaymentMethodMapperInterface $paymentMethodMapper,
        PaymentMethodWriterInterface $paymentMethodWriter,
        PaymentMeansSubShopsWriterInterface $paymentMeansSubShopsWriter
    ) {
        $this->paymentMethodsProvider = $paymentMethodsProvider;
        $this->shopRepository = $shopRepository;
        $this->usedFallbackConfigRule = $usedFallbackConfigRule;
        $this->paymentMethodMapper = $paymentMethodMapper;
        $this->paymentMethodWriter = $paymentMethodWriter;
        $this->paymentMeansSubShopsWriter = $paymentMeansSubShopsWriter;
    }

    public function importAll(): \Generator
    {
        /** @var Shop $shop */
        foreach ($this->shopRepository->findAll() as $shop) {
            if (($this->usedFallbackConfigRule)($shop->getId())) {
                $this->paymentMeansSubShopsWriter->registerAdyenPaymentMethodForSubShop($shop->getId());
                yield ImportResult::successSubshopFallback($shop, ImportStatus::updated());

                continue;
            }

            yield from $this->import($shop);
        }
    }

    public function importForShop(Shop $shop): \Generator
    {
        yield from $this->import($shop);
    }

    /**
     * @psalm-return \Generator<ImportResult>
     */
    private function import(Shop $shop): \Generator
    {
        $paymentMethods = $this->paymentMethodMapper->mapFromAdyen(($this->paymentMethodsProvider)($shop));
        foreach ($paymentMethods as $adyenPaymentMethod) {
            try {
                yield $this->paymentMethodWriter->__invoke($adyenPaymentMethod, $shop);
            } catch (\Exception $exception) {
                yield ImportResult::fromException($shop, $adyenPaymentMethod, $exception);
            }
        }
    }
}
