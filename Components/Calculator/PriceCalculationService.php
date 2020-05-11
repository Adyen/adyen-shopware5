<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Calculator;

/**
 * Class PriceCalculationService
 * @package MeteorAdyen\Components\Calculator
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
