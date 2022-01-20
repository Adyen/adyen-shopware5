<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation\InstallHandler;

interface Installer
{
    public function install(): void;

    public function isFallback(): bool;
}
