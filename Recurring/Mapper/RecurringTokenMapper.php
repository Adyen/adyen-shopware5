<?php

declare(strict_types=1);

namespace AdyenPayment\Recurring\Mapper;

use AdyenPayment\Models\PaymentResultCodes;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;

final class RecurringTokenMapper implements RecurringTokenMapperInterface
{
    public function __invoke(array $rawData): RecurringPaymentToken
    {
        return RecurringPaymentToken::create(
            $rawData['additionalData']['recurring.shopperReference'] ?? '',
            $rawData['additionalData']['recurring.recurringDetailReference'] ?? '',
            $rawData['pspReference'] ?? '',
            $rawData['merchantReference'] ?? '',
            isset($rawData['resultCode']) ?
                PaymentResultCodes::load($rawData['resultCode']) :
                PaymentResultCodes::refused(),
            $rawData['amount']['value'] ?? 0,
            $rawData['amount']['currency'] ?? ''
        );
    }
}
