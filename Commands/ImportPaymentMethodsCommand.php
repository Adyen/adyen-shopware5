<?php

declare(strict_types=1);

namespace AdyenPayment\Commands;

use AdyenPayment\Import\PaymentMethodImporterInterface;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ImportPaymentMethodsCommand extends ShopwareCommand
{
    /**
     * @var PaymentMethodImporterInterface
     */
    private $paymentMethodImporter;

    public function __construct(PaymentMethodImporterInterface $paymentMethodImporter)
    {
        $this->paymentMethodImporter = $paymentMethodImporter;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Import Adyen payment methods for all stores');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $counter = 0;
        $io = new SymfonyStyle($input, $output);

        /** @var ImportResult $importResult */
        foreach ($this->paymentMethodImporter->importAll() as $importResult) {
            ++$counter;

            $io->text(sprintf(
                'Imported payment method %s for store %s',
                $importResult->getPaymentMethod() ? $importResult->getPaymentMethod()->getType() : 'n/a',
                $importResult->getShop()->getName()
            ));
        }

        $io->success(sprintf('Successfully imported %s Payment Method(s)', $counter));

        return 0;
    }
}
