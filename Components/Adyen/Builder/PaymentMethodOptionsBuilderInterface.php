<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\Builder;

interface PaymentMethodOptionsBuilderInterface
{
    public function __invoke(): array;
}
