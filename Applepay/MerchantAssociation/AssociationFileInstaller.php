<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation;

use AdyenPayment\Applepay\MerchantAssociation\Model\InstallResult;

interface AssociationFileInstaller
{
    /**
     * @return \Generator<InstallResult>|InstallResult[]
     */
    public function __invoke(): \Generator;
}
