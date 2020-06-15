<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Calculator;

/**
 * Class PriceCalculationService
 * @package AdyenPayment\Components\Calculator
 */
class PriceCalculationService
{
    public function getAmountExcludingTax($amount, $tax)
    {
        return round($amount / (1 + $tax /100), 2);
    }

    public function getTaxAmount($amount, $tax)
    {
        return round($amount - $this->getAmountExcludingTax($amount, $tax), 2);
    }
}
