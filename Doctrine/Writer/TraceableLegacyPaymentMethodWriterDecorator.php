<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use AdyenPayment\Shopware\Repository\PaymentRepositoryInterface;
use Psr\Log\LoggerInterface;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

/**
 * @internal
 * This class will become eventually outdated,
 * only required when plugin version 3.0.x or 3.1.x was installed
 */
final class TraceableLegacyPaymentMethodWriterDecorator implements PaymentMethodWriterInterface
{
    private PaymentRepositoryInterface $paymentRepository;
    private PaymentMethodWriterInterface $paymentMethodWriter;
    private PaymentAttributeWriterInterface $paymentAttributeWriter;
    private LoggerInterface $logger;

    public function __construct(
        PaymentMethodWriterInterface $paymentMethodWriter,
        PaymentRepositoryInterface $paymentRepository,
        PaymentAttributeWriterInterface $paymentAttributeWriter,
        LoggerInterface $logger
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->paymentMethodWriter = $paymentMethodWriter;
        $this->paymentAttributeWriter = $paymentAttributeWriter;
        $this->logger = $logger;
    }

    public function __invoke(PaymentMethod $adyenPaymentMethod, Shop $shop): ImportResult
    {
        if ($adyenPaymentMethod->adyenType()->type() === $adyenPaymentMethod->uniqueIdentifier()) {
            return ($this->paymentMethodWriter)($adyenPaymentMethod, $shop);
        }

        // legacy code had adyen 'type' stored as identifier
        $paymentMean = $this->paymentRepository->findByUniqueIdentifier($adyenPaymentMethod->adyenType()->type());
        if (!$paymentMean) {
            return ($this->paymentMethodWriter)($adyenPaymentMethod, $shop);
        }

        $this->log($paymentMean, $adyenPaymentMethod);
        ($this->paymentAttributeWriter)($paymentMean->getId(), $adyenPaymentMethod);

        return ($this->paymentMethodWriter)($adyenPaymentMethod, $shop);
    }

    private function log(Payment $paymentMean, PaymentMethod $adyenPaymentMethod): void
    {
        $this->logger->notice(sprintf('Updating legacy payment mean adyen "%s" to "%s"',
            $adyenPaymentMethod->adyenType()->type(),
            $adyenPaymentMethod->uniqueIdentifier(),
        ), [
            'shopware payment mean' => [
                'id' => $paymentMean->getId(),
                'name' => $paymentMean->getName(),
                'additional description' => $paymentMean->getAdditionalDescription(),
            ],
            'adyen payment method' => [
                'name' => $adyenPaymentMethod->name(),
                'type' => $adyenPaymentMethod->adyenType()->type(),
                'unique identifier' => $adyenPaymentMethod->uniqueIdentifier(),
            ],
        ]);
    }
}
