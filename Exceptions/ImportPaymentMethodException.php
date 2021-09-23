<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware\Models\Shop\Shop;

class ImportPaymentMethodException extends \Exception
{
    public function missingId(PaymentMethod $paymentMethod, Shop $shop): self
    {
        return new self(
            sprintf(
                'Could not import %s payment method with ID: %s and type: %s from Adyen for shop: %s.',
                $paymentMethod->isStoredPayment() ? "stored" : "",
                $paymentMethod->getId(),
                $paymentMethod->getType(),
                $shop->getName()
            )
        );
    }
}
