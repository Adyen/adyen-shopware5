<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

interface ImageLogoProviderInterface
{
    public function getAdyenImageByType($type): string;
}
