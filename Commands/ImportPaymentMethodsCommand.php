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

        /** @var ImportResult $importPaymentMethod */
        foreach ($this->paymentMethodImporter->__invoke() as $importPaymentMethod) {
            ++$counter;

            if ($importPaymentMethod->isUpdated()) {
                $io->text(sprintf(
                    'Updated payment method %s for store %s',
                    $importPaymentMethod->getPaymentMethod()->getType(),
                    $importPaymentMethod->getShop()->getName()
                ));
                continue;
            }

            $io->text(sprintf(
                'Imported payment method %s for store %s',
                $importPaymentMethod->getPaymentMethod()->getType(),
                $importPaymentMethod->getShop()->getName()
            ));
        }

        $io->success(sprintf('Successfully imported %s Payment Method(s)', $counter));

        return 0;
    }
}
