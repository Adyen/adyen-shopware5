<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

final class DataConversion
{
    public function getISO3166FromLocale($locale): string
    {
        return str_replace('_', '-', $locale);
    }
}
