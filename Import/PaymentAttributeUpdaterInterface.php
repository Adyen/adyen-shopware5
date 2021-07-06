<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

interface PaymentAttributeUpdaterInterface
{
    /**
     * @param string[] $columns
     */
    public function setReadonlyOnAdyenPaymentAttributes(array $columns, bool $readOnly);
}
