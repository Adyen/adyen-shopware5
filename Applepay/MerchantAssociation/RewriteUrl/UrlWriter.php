<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation\RewriteUrl;

interface UrlWriter
{
    public function __invoke(): void;
}
