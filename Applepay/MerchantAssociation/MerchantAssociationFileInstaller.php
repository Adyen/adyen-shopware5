<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation;

use AdyenPayment\Applepay\MerchantAssociation\InstallHandler\Installer;
use AdyenPayment\Applepay\MerchantAssociation\Model\InstallResult;

final class MerchantAssociationFileInstaller implements AssociationFileInstaller
{
    /** @var Installer[] */
    private $installers;

    public function __construct(Installer ...$installers)
    {
        $this->installers = $installers;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(): \Generator
    {
        foreach ($this->installers as $installer) {
            try {
                $installer->install();

                yield InstallResult::fromSuccess()
                    ->withFallback($installer->isFallback());

                return;
            } catch (\Exception $exception) {
                yield InstallResult::fromException($exception)
                    ->withFallback($installer->isFallback());
            }
        }
    }
}
