<?php

declare(strict_types=1);

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
use AdyenPayment\Import\PaymentMethodImporterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_ImportPaymentMethods extends Shopware_Controllers_Backend_ExtJs
{
    /** @var PaymentMethodImporterInterface */
    private $paymentMethodImporter;
    /** @var LoggerInterface */
    private $logger;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->paymentMethodImporter = $this->get('AdyenPayment\Import\PaymentMethodImporter');
        $this->logger = $this->get('adyen_payment.logger');
    }

    public function importAction()
    {
        try {
            $counter = 0;

            $counter = count(iterator_to_array(
                $this->paymentMethodImporter->importAll()
            ));

            $this->response->setHttpResponseCode(Response::HTTP_OK);
            $this->View()->assign('responseText', sprintf(
                'Imported successfully %s payment method(s)',
                $counter
            ));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->View()->assign(
                'responseText',
                sprintf(
                    'Import of payment methods failed. Please check the logs for more details.'
                )
            );
        }
    }
}
