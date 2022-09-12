<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation;

use Psr\Log\LoggerInterface;

final class TraceableFileInstaller implements AssociationFileInstaller
{
    private AssociationFileInstaller $installer;
    private LoggerInterface $logger;

    public function __construct(AssociationFileInstaller $installer, LoggerInterface $logger)
    {
        $this->installer = $installer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(): \Generator
    {
        $installers = ($this->installer)();
        foreach ($installers as $installResult) {
            if ($installResult->success()) {
                yield $installResult;

                continue;
            }

            if ($installResult->exception() instanceof \Exception) {
                $this->logger->error($installResult->exception()->getMessage());
            }

            yield $installResult;
        }
    }
}
