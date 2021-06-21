<?php

declare(strict_types=1);

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
use AdyenPayment\Import\PaymentMethodImporter;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_ImportPaymentMethods extends Shopware_Controllers_Backend_ExtJs
{
    /** @var PaymentMethodImporter */
    private $paymentMethodImporter;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->paymentMethodImporter = $this->get('AdyenPayment\Import\PaymentMethodImporter');
    }

    public function importAction()
    {
        $this->View()->assign(
            'responseText',
            sprintf('Successfully imported %s payment method(s)', 1)
        );
//        $counter = 0;
//
//        foreach ($this->paymentMethodImporter->__invoke() as $importPaymentMethod)
//        {
//            ++$counter;
//        }
//
//        $this->response->setHttpResponseCode(Response::HTTP_OK);
//        $this->View()->assign(
//            'responseText',
//            sprintf('Successfully imported %s payment method(s)', $counter)
//        );
    }
}