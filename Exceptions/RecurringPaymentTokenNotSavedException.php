<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\TokenIdentifier;

class RecurringPaymentTokenNotSavedException extends \Exception
{
    public static function withId(TokenIdentifier $tokenIdentifier): self
    {
        return new self(
            'Recurring payment token with id: '.$tokenIdentifier->identifier().' could not be saved.'
        );
    }
}
