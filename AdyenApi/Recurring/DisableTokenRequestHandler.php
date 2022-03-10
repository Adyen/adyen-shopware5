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

    public function disableToken(string $recurringTokenId, Shop $shop): ApiResponse
    {
        // @TODO: replace with a new service.
        $customerNumber = $this->paymentMethodService->provideCustomerNumber();
        if ('' === $customerNumber) {
            return ApiResponse::empty();
        }
        $recurringTransport = $this->transportFactory->recurring($shop);

        $payload = [
            'shopperReference' => $customerNumber,
            'recurringDetailReference' => $recurringTokenId,
        ];

        $result = $recurringTransport->disable($payload);

        $success = (200 === $result['status']) && false !== mb_strpos(($result['message'] ?? ''), 'successfully-disabled');

        return ApiResponse::create($result['status'], $success, $result['message']);
    }
}
