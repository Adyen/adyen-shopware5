<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\Recurring;

use AdyenPayment\AdyenApi\Model\ApiResponse;
use AdyenPayment\AdyenApi\TransportFactoryInterface;
use AdyenPayment\Session\CustomerNumberProviderInterface;
use Shopware\Models\Shop\Shop;

final class DisableTokenRequestHandler implements DisableTokenRequestHandlerInterface
{
    /** @var TransportFactoryInterface */
    private $transportFactory;

    /** @var CustomerNumberProviderInterface */
    private $customerNumberProvider;

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

        $response = (string) ($result['response'] ?? '');
        $resultMessage = (string) ($result['message'] ?? '');
        $isSuccessfullyDisabled = $this->isSuccessfullyDisabled($response);

        return ApiResponse::create($isSuccessfullyDisabled, $resultMessage);
    }

    private function isSuccessfullyDisabled(string $response): bool
    {
        if (false === mb_strpos($response, 'successfully-disabled')) {
            return false;
        }

        return true;
    }
}
