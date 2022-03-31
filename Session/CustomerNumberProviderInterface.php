<?php

declare(strict_types=1);

namespace AdyenPayment\Session;

interface CustomerNumberProviderInterface
{
    public function __invoke(): string;
}
