<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

class UmbrellaPaymentMeanNotFoundException extends \Exception
{
    public static function missingUmbrellaPaymentMean(): self
    {
        return new static('Umbrella payment mean not found.');
    }
}
