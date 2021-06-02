<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

interface PaymentMethodImporterInterface
{
    public function __invoke(): \Generator;
}
