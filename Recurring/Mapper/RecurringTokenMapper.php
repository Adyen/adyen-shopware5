<?php

declare(strict_types=1);

namespace AdyenPayment\Recurring\Mapper;

use AdyenPayment\Exceptions\InvalidPaymentsResponseException;
use AdyenPayment\Models\PaymentResultCode;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use AdyenPayment\Models\TokenIdentifier;

final class RecurringTokenMapper implements RecurringTokenMapperInterface
{
    public function __invoke(array $rawData): RecurringPaymentToken
    {
        if (0 === count($rawData)) {
            throw InvalidPaymentsResponseException::invalid();
        }

        return RecurringPaymentToken::create(
            TokenIdentifier::generate(),
            $rawData['additionalData']['recurring.shopperReference'] ?? '',
            $rawData['additionalData']['recurring.recurringDetailReference'] ?? '',
            $rawData['pspReference'] ?? '',
            $rawData['merchantReference'] ?? '',
            array_key_exists('resultCode', $rawData) ?
                PaymentResultCode::load($rawData['resultCode']) :
                PaymentResultCode::invalid(),
            $rawData['amount']['value'] ?? 0,
            $rawData['amount']['currency'] ?? ''
        );
    }
}
