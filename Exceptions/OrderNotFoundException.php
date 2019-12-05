<?php

namespace MeteorAdyen\Exceptions;

/**
 * Class OrderNotFoundException
 * @package MeteorAdyen\Exceptions
 */
class OrderNotFoundException extends \Exception
{
    /**
     * OrderNotFoundException constructor.
     * @param string $orderNumber
     */
    public function __construct(
        string $orderNumber
    ) {
        parent::__construct("Order does not exist (" . $orderNumber . ")");
    }
}
