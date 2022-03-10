<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\Recurring;

use AdyenPayment\AdyenApi\Model\ApiResponse;
use AdyenPayment\AdyenApi\TransportFactoryInterface;
use AdyenPayment\Components\Adyen\PaymentMethodServiceInterface;
use Shopware\Models\Shop\Shop;

final class DisableTokenRequestHandler implements DisableTokenRequestHandlerInterface
{
    private PaymentMethodServiceInterface $paymentMethodService;
    private TransportFactoryInterface $transportFactory;

    public function __construct(
        PaymentMethodServiceInterface $paymentMethodService,
        TransportFactoryInterface $transportFactory
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

        $result = $recurringTransport->disable($payload);

        return ApiResponse::create($result['statusCode'], $result['success'], $result['message']);
    }
}
