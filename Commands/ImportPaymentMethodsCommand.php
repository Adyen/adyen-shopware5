<?php

declare(strict_types=1);

namespace AdyenPayment\Commands;

use AdyenPayment\Import\PaymentMethodImporterInterface;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ImportPaymentMethodsCommand extends ShopwareCommand
{
    private PaymentMethodImporterInterface $importer;

    public function __construct(PaymentMethodImporterInterface $importer)
    {
        $this->importer = $importer;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Import Adyen payment methods for all stores');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $total = $success = 0;
        $io = new SymfonyStyle($input, $output);

        foreach ($this->importer->importAll() as $result) {
            ++$total;

            if (!$result->isSuccess()) {
                $io->warning(sprintf('Could not import payment method %s for store %s, message: %s.',
                    $result->getPaymentMethod() ? $result->getPaymentMethod()->getType() : 'n/a',
                    $result->getShop()->getName(),
                    $result->getException() ? $result->getException()->getMessage() : 'n/a'
                ));

                continue;
            }

            ++$success;
            $io->text(sprintf('Imported payment method %s for store %s',
                $result->getPaymentMethod() ? $result->getPaymentMethod()->getType() : 'n/a',
                $result->getShop()->getName()
            ));
        }

        $io->success(sprintf('Successfully imported %s of %s Payment Method(s)', $success, $total));

        return 0;
    }
}
