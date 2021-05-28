<?php

declare(strict_types=1);

namespace AdyenPayment\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ConstraintViolationFactory
{
    public static function create(string $message): ConstraintViolationInterface
    {
        return new ConstraintViolation($message, null, [], null, '', null);
    }
}
