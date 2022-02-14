<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use AdyenPayment\Components\BasketService;
use AdyenPayment\Models\PaymentResultCodes;

final class PaymentResultCodeHandler implements PaymentResultCodeHandlerInterface
{
    private BasketService $basketService;

    public function __construct(BasketService $basketService)
    {
        $this->basketService = $basketService;
    }

    public function __invoke(array $paymentResponseInfo): void
    {
        try {
            PaymentResultCodes::load($paymentResponseInfo['resultCode']);
        } catch (\InvalidArgumentException $exception) {
            $this->handlePaymentDataError($paymentResponseInfo);
        }
    }

    private function handlePaymentDataError(array $paymentResponseInfo): void
    {
        if (isset($paymentResponseInfo['merchantReference'])) {
            $this->basketService->cancelAndRestoreByOrderNumber($paymentResponseInfo['merchantReference']);
        }
    }
}
