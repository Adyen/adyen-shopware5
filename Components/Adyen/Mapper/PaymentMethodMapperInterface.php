<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\Mapper;

interface PaymentMethodMapperInterface
{
    public function mapFromAdyen(array $data): \Generator;
}