<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Calculator;

/**
 * Class PriceCalculationService.
 */
class PriceCalculationService
{
    public function getAmountExcludingTax($amount, $tax): float
    {
        return round($amount / (1 + $tax / 100), 2);
    }

    public function getTaxAmount($amount, $tax): float
    {
        return round($amount - $this->getAmountExcludingTax($amount, $tax), 2);
    }
}
