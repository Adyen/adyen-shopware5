<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\Mapper;

interface PaymentMethodMapperInterface
{
    /**
     * @return \Generator<\AdyenPayment\Models\Payment\PaymentMethod>
     */
    public function mapFromAdyen(array $data): \Generator;
}
