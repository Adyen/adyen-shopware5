<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

interface PaymentAttributeUpdaterInterface
{
    public function setReadonlyOnAdyenTypePaymentAttribute(bool $readOnly);
}