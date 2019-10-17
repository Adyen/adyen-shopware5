<?php
declare(strict_types=1);

namespace MeteorAdyen\Models\Payload;

/**
 * Class PayContext
 * @package MeteorAdyen\Models
 */
class PayContext
{
    /**
     * @return array
     */
    public function paymentInfo(): array
    {
    }

    /**
     * @return array
     */
    public function order(): array
    {
    }

    /**
     * todo: get additional browserinfo with javascript
     * window.screen.colorDepth
     * window.screen.width
     * window.screen.height
     * navigator.javaEnabled()
     * new Date().getTimezoneOffset()
     * navigator.language
     * navigator.userAgent
     *
     *
     * @return array
     */
    public function browserInfo(): array
    {
        return [
            'acceptHeader' => $_SERVER['HTTP_ACCEPT'],
        ];
    }
}