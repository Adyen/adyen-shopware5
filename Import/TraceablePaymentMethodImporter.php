<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Models\PaymentMethod\ImportResult;
use Psr\Log\LoggerInterface;
use Shopware\Models\Shop\Shop;

final class TraceablePaymentMethodImporter implements PaymentMethodImporterInterface
{
    /** @var PaymentMethodImporterInterface */
    private $paymentMethodImporter;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(PaymentMethodImporterInterface $paymentMethodImporter, LoggerInterface $logger)
    {
        $this->paymentMethodImporter = $paymentMethodImporter;
        $this->logger = $logger;
    }

    public function importAll(): \Generator
    {
        foreach ($this->paymentMethodImporter->importAll() as $importResult) {
            $this->log($importResult);

            yield $importResult;
        }
    }

    public function importForShop(Shop $shop): \Generator
    {
        foreach ($this->paymentMethodImporter->importForShop($shop) as $importResult) {
            $this->log($importResult);

            yield $importResult;
        }
    }

    private function log(ImportResult $importResult): void
    {
        $paymentMethod = $importResult->getPaymentMethod();
        if ($importResult->isSuccess()) {
            $this->logger->info('Adyen payment method imported', [
                'shop id' => $importResult->getShop()->getId(),
                'shop name' => $importResult->getShop()->getName(),
                'payment method' => $paymentMethod ?
                    $paymentMethod->adyenType()->type().' '.$paymentMethod->name() :
                    'all',
            ]);

            return;
        }

        $exception = $importResult->getException();
        $this->logger->error('Adyen payment method could not be imported', [
            'shop id' => $importResult->getShop()->getId(),
            'shop name' => $importResult->getShop()->getName(),
            'payment type' => $paymentMethod ? $paymentMethod->adyenType()->type() : 'n/a',
            'payment name' => $paymentMethod ? $paymentMethod->name() : 'n/a',
            'message' => $exception ? $exception->getMessage() : 'n/a',
            'exception' => $exception,
        ]);
    }
}
