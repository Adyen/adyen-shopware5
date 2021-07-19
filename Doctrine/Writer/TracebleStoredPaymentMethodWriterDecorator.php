<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Psr\Log\LoggerInterface;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

class TracebleStoredPaymentMethodWriterDecorator implements StoredPaymentMethodWriterInterface
{
    /** @var StoredPaymentMethodWriterInterface */
    private $storedPaymentMethodWriter;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        StoredPaymentMethodWriterInterface $storedPaymentMethodWriter,
        LoggerInterface $logger
    )
    {
        $this->storedPaymentMethodWriter = $storedPaymentMethodWriter;
        $this->logger = $logger;
    }

    public function __invoke(PaymentMethod $adyenStoredPaymentMethod, Shop $shop): ImportResult
    {
        return $this->log($this->storedPaymentMethodWriter->__invoke($adyenStoredPaymentMethod, $shop));
    }

    private function log(ImportResult $importResult): ImportResult
    {
        if ($importResult->isSuccess()) {
            $this->logger->info('Adyen stored payment method imported', [
                'shop id' => $importResult->getShop()->getId(),
                'shop name' => $importResult->getShop()->getName(),
                'payment method' => $importResult->getPaymentMethod()->getType(),
            ]);

            return $importResult;
        }

        $this->logger->error('Adyen stored payment method could not be imported', [
            'shop id' => $importResult->getShop()->getId(),
            'shop name' => $importResult->getShop()->getName(),
            'payment method' => $importResult->getPaymentMethod()->getType(),
        ]);

        return $importResult;
    }
}
