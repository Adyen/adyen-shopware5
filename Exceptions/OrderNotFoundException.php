<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

/**
 * Class OrderNotFoundException.
 */
class OrderNotFoundException extends \Exception
{
    /**
     * OrderNotFoundException constructor.
     */
    public function __construct(
        string $orderNumber
    ) {
        parent::__construct('Order does not exist ('.$orderNumber.')');
    }
}
