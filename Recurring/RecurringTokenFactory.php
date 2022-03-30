<?php

declare(strict_types=1);

namespace AdyenPayment\Recurring;

use AdyenPayment\Exceptions\InvalidPaymentsResponseException;
use AdyenPayment\Models\PaymentResultCode;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use AdyenPayment\Models\TokenIdentifier;

final class RecurringTokenFactory implements RecurringTokenFactoryInterface
{
    public static function create(array $data): RecurringPaymentToken
    {
        if (0 === count($data)) {
            throw InvalidPaymentsResponseException::empty();
        }

        return RecurringPaymentToken::create(
            TokenIdentifier::generate(),
            $data['additionalData']['recurring.shopperReference'] ?? '',
            $data['additionalData']['recurring.recurringDetailReference'] ?? '',
            $data['pspReference'] ?? '',
            $data['merchantReference'] ?? '',
            array_key_exists('resultCode', $data) ?
                PaymentResultCode::load($data['resultCode']) :
                PaymentResultCode::invalid(),
            $data['amount']['value'] ?? 0,
            $data['amount']['currency'] ?? ''
        );
    }
}
