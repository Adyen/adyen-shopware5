<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Models\PaymentMethod\ImportResult;
use Psr\Log\LoggerInterface;

class TraceablePaymentMethodImporter implements PaymentMethodImporterInterface
{
    /**
     * @var PaymentMethodImporterInterface
     */
    private $paymentMethodImporter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PaymentMethodImporterInterface $paymentMethodImporter,
        LoggerInterface $logger
    ) {
        $this->paymentMethodImporter = $paymentMethodImporter;
        $this->logger = $logger;
    }

    public function __invoke(): \Generator
    {
        foreach (($this->paymentMethodImporter)() as $importResult) {
            $this->log($importResult);
            yield $importResult;
        }
    }

    private function log(ImportResult $importResult)
    {
        if ($importResult->isSuccess()) {
            $this->logger->info('Adyen payment method imported', [
                'shop id' => $importResult->getShop()->getId(),
                'shop name' => $importResult->getShop()->getName(),
                'payment method' => $importResult->getPaymentMethod()->getType(),
            ]);

            return;
        }

        $this->logger->error('Adyen payment method could not be imported', [
            'shop id' => $importResult->getShop()->getId(),
            'shop name' => $importResult->getShop()->getName(),
            'payment method' => $importResult->getPaymentMethod()->getType(),
        ]);
    }
}
