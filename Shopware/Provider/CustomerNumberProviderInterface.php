<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Provider;

interface CustomerNumberProviderInterface
{
    public function __invoke(): string;
}
