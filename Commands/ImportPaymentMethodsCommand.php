<?php

declare(strict_types=1);

namespace AdyenPayment\Commands;

use AdyenPayment\Import\PaymentMethodImporterInterface;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportPaymentMethodsCommand extends ShopwareCommand
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


        throw new \Exception('TODO: create implementation');

        // TODO following is pseude-code, update with actual implementation (Generator)
        foreach ($this->paymentMethodImporter->__invoke() as $importPaymentMethod) {
            ++$counter;

            $io->text(sprintf(
                'Imported payment method %s for store %s',
                $importPaymentMethod->getPaymentMethodName(),
                $importPaymentMethod->getStoreName()
            ));
        }

        $io->success(sprintf('Successfully imported %s Payment Methods', $counter));

        return 0;
    }
}
