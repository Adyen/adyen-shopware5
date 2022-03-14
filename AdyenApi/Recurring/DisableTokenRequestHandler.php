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

        $validResponse = Response::HTTP_OK === $result['status'];
        $validMessage = false !== mb_strpos(($result['message'] ?? ''), 'successfully-disabled');

        return ApiResponse::create($result['status'], ($validResponse && $validMessage), $result['message']);
    }
}
