<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\TokenIdentifier;

final class RecurringPaymentTokenNotSavedException extends \RuntimeException
{
    public static function withId(TokenIdentifier $tokenIdentifier): self
    {
        return new self('Recurring payment token not saved with id:'.$tokenIdentifier->identifier());
    }
}
