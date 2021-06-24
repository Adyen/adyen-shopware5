<?php

declare(strict_types=1);

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
use AdyenPayment\Import\PaymentMethodImporterInterface;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_ImportPaymentMethods extends Shopware_Controllers_Backend_ExtJs
{
    /** @var PaymentMethodImporterInterface */
    private $paymentMethodImporter;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->paymentMethodImporter = $this->get('AdyenPayment\Import\PaymentMethodImporter');
    }

    public function importAction()
    {
        try {
//            $count = 1;
//            $this->View()->assign(
//                'responseText',
//                sprintf('Successfully imported %s payment method(s)',
//                $count
//                )
//            );
            $counter = count(iterator_to_array(
                $this->paymentMethodImporter->__invoke()
            ));

            $this->response->setHttpResponseCode(Response::HTTP_OK);
            $this->View()->assign(
                'responseText',
                sprintf(
                    'Successfully imported payment method(s)'
                )
            );
        } catch (Exception $e)
        {
            $this->View()->assign(
                'responseText',
                sprintf(
                    'It fails'
                )
            );
        }

    }
}
