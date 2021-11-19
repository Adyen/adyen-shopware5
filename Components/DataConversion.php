<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

/**
 * Class DataConversion.
 */
class DataConversion
{
    /**
     * @param $locale
     */
    public function getISO3166FromLocale($locale): string
    {
        return str_replace('_', '-', $locale);
    }
}
