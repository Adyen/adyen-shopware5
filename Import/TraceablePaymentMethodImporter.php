<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

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
        foreach (($this->paymentMethodImporter)() as $importPaymentMethod) {
            $this->log($importPaymentMethod);
            yield $importPaymentMethod;
        }
    }

    private function log(/*TODO: type*/$importPaymentMethod)
    {
        if ($importPaymentMethod->isSuccessful()) {
            $this->logger->info('Adyen payment method imported', [
                'storefront' => $importPaymentMethod->getStoreFrontName(), // values: "Main" OR "specific storefront"
                'payment method' => $importPaymentMethod->getName(),
            ]);

            return;
        }

        $this->logger->error('Adyen payment method could not be imported', [
            'storefront' => $importPaymentMethod->getStoreFrontName(), // values: "Main" OR "specific storefront"
            'payment method' => $importPaymentMethod->getName(),
        ]);
    }
}
