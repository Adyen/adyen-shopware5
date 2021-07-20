<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware\Models\Shop\Shop;

class ImportPaymentMethodException extends \Exception
{
    public static function missingId(PaymentMethod $paymentMethod, Shop $shop): self
    {
        return new static(
            "Could not import "
            . $paymentMethod->isStoredPayment() ? "stored" : "" .
            "payment method with id: "
            . $paymentMethod->getId() .
            " and type: "
            . $paymentMethod->getType() .
            " from adyen for shop: "
            . $shop->getName() .
            "."
        );
    }
}
