<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\Recurring;

use AdyenApi\Model\ApiResponse;
use AdyenPayment\AdyenApi\TransportFactory;
use AdyenPayment\Components\Adyen\PaymentMethodServiceInterface;
use Shopware\Models\Shop\Shop;

final class DisableTokenRequestHandler implements DisableTokenRequestHandlerInterface
{
    private PaymentMethodServiceInterface $paymentMethodService;
    private TransportFactory $transportFactory;

    public function __construct(
        PaymentMethodServiceInterface $paymentMethodService,
        TransportFactory $transportFactory
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->transportFactory = $transportFactory;
    }

    public function disableToken(string $recurringTokenId, Shop $shop): ?ApiResponse
    {
        $customerNumber = $this->paymentMethodService->provideCustomerNumber();
        if ('' === $customerNumber) {
            return null;
        }
        $recurringTransport = $this->transportFactory->recurring($shop);

        $payload = [
            'shopperReference' => $customerNumber,
            'recurringDetailReference' => $recurringTokenId,
        ];

        return ApiResponse::create(...$recurringTransport->disable($payload));
    }
}
