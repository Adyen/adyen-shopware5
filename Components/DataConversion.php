<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

/**
 * Class DataConversion
 * @package MeteorAdyen\Components
 */
class DataConversion
{
    /**
     * @param $locale
     * @return string
     */
    public function getISO3166FromLocale($locale): string
    {
        return str_replace('_', '-', $locale);
    }
}
