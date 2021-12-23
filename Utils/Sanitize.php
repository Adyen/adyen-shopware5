<?php

declare(strict_types=1);

namespace AdyenPayment\Utils;

final class Sanitize
{
    public static function removeNonWord(string $raw, string $replace = '_'): string
    {
        return trim(preg_replace('/\W/', $replace, $raw), $replace);
    }

    public static function escape(string $raw): string
    {
        return htmlspecialchars($raw, ENT_NOQUOTES);
    }
}
