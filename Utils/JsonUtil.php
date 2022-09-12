<?php

declare(strict_types=1);

namespace AdyenPayment\Utils;

/**
 * Class JsonUtil. Implements compatibility for json_encode and json_decode for PHP versions < 7.3.
 */
class JsonUtil
{
    /**
     * Wrapper function for json_encode.
     *
     * @param $value
     *
     * @throws \JsonException
     * @throws \Exception
     *
     * @return false|string
     *
     * @see https://www.php.net/manual/en/function.json-encode.php
     */
    public static function encode($value, int $flags = 0, int $depth = 512): string
    {
        if (defined('JSON_THROW_ON_ERROR')) {
            return json_encode($value, $flags | JSON_THROW_ON_ERROR, $depth);
        }

        $json = json_encode($value, $flags, $depth);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception('Could not encode the data to json.');
        }

        return $json;
    }

    /**
     * Wrapper function for json_decode.
     *
     * @throws \JsonException
     * @throws \Exception
     *
     * @return mixed
     *
     * @see https://www.php.net/manual/en/function.json-decode.php
     */
    public static function decode(string $json, ?bool $associative = null, int $depth = 512, int $flags = 0)
    {
        if (defined('JSON_THROW_ON_ERROR')) {
            return json_decode($json, $associative, $depth, $flags | JSON_THROW_ON_ERROR);
        }

        $json = json_decode($json, $associative, $depth, $flags);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception('Could not encode the data to json.');
        }

        return $json;
    }
}
