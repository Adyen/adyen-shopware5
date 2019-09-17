<?php

declare(strict_types=1);

namespace EushCustomerLogin\Service;

class DataConversion
{
    /**
     * Return the number of decimals for the specified currency
     * @param $currency
     * @return int
     */
    public function getDecimalNumbers($currency): int
    {
        switch ($currency) {
            case "CVE":
            case "DJF":
            case "GNF":
            case "IDR":
            case "JPY":
            case "KMF":
            case "KRW":
            case "PYG":
            case "RWF":
            case "UGX":
            case "VND":
            case "VUV":
            case "XAF":
            case "XOF":
            case "XPF":
                $format = 0;
                break;
            case "BHD":
            case "IQD":
            case "JOD":
            case "KWD":
            case "LYD":
            case "OMR":
            case "TND":
                $format = 3;
                break;
            default:
                $format = 2;
        }
        return $format;
    }

    /**
     * @param $locale
     * @return string
     */
    public function getISO3166FromLocale($locale): string
    {
        return str_replace('_', '-', $locale);
    }
}