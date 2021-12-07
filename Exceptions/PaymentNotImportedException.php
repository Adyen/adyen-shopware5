<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentNotImportedException extends \RuntimeException
{
    private function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forPayment(PaymentMethod $adyenPaymentMethod, Payment $swPayment, Shop $shop): self
    {
        return new self(sprintf(
            'Could not import payment method id: %s, name: "%s", type: "%s"%s for shop: "%s" ("%s").',
            $swPayment->getId(),
            $swPayment->getName(),
            $adyenPaymentMethod->getType(),
            $adyenPaymentMethod->isStoredPayment() ? ', stored payment id: "'.$adyenPaymentMethod->getId().'"' : '',
            $shop->getId(),
            $shop->getName()
        ));
    }
}
