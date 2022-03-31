<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\HttpClient;

use Symfony\Component\Validator\ConstraintViolationList;

interface ConfigValidatorInterface
{
    public function validate(int $shopId): ConstraintViolationList;
}
