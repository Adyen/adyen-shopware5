<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\Recurring;

use AdyenPayment\AdyenApi\Model\ApiResponse;
use AdyenPayment\AdyenApi\TransportFactoryInterface;
use AdyenPayment\Session\CustomerNumberProviderInterface;
use Shopware\Models\Shop\Shop;
use Symfony\Component\HttpFoundation\Response;

final class DisableTokenRequestHandler implements DisableTokenRequestHandlerInterface
{
    private TransportFactoryInterface $transportFactory;
    private CustomerNumberProviderInterface $customerNumberProvider;

    public function __construct(
        TransportFactoryInterface $transportFactory,
        CustomerNumberProviderInterface $customerNumberProvider
    ) {
        $this->transportFactory = $transportFactory;
        $this->customerNumberProvider = $customerNumberProvider;
    }

    public function disableToken(string $recurringTokenId, Shop $shop): ApiResponse
    {
        $customerNumber = ($this->customerNumberProvider)();
        if ('' === $customerNumber) {
            return ApiResponse::empty();
        }
        $recurringTransport = $this->transportFactory->recurring($shop);

        $payload = [
            'shopperReference' => $customerNumber,
            'recurringDetailReference' => $recurringTokenId,
        ];

        $result = $recurringTransport->disable($payload);

        $resultStatus = (int) ($result['status'] ?? 400);
        $resultMessage = (string) ($result['message'] ?? '');
        $isSuccessfullyDisabled = $this->isSuccessfullyDisabled($resultStatus, $resultMessage);

        return ApiResponse::create($resultStatus, $isSuccessfullyDisabled, $resultMessage);
    }

    private function isSuccessfullyDisabled(int $resultStatus, string $resultMessage): bool
    {
        if (Response::HTTP_OK !== $resultStatus) {
            return false;
        }

        if (false === mb_strpos($resultMessage, 'successfully-disabled')) {
            return false;
        }

        return true;
    }
}
