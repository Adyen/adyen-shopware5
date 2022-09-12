<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use Psr\Log\LoggerInterface;

final class TraceableEnrichedPaymentMeanProvider implements EnrichedPaymentMeanProviderInterface
{
    /** @var EnrichedPaymentMeanProviderInterface */
    private $enrichedPaymentMeanProvider;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider,
        LoggerInterface $logger
    ) {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
        $this->logger = $logger;
    }

    /**
     * @throws \Adyen\AdyenException
     */
    public function __invoke(PaymentMeanCollection $paymentMeans): PaymentMeanCollection
    {
        try {
            return ($this->enrichedPaymentMeanProvider)($paymentMeans);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        return new PaymentMeanCollection();
    }
}
