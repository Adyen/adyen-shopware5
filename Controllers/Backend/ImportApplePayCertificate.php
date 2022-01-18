<?php

declare(strict_types=1);

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
use AdyenPayment\Certificate\Filesystem\CertificateReaderInterface;
use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_ImportApplePayCertificate extends Shopware_Controllers_Backend_ExtJs
{
    private ApplePayTransportHandlerInterface $applePayTransportHandler;
    private CertificateReaderInterface $certificateReader;
    private LoggerInterface $logger;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->applePayTransportHandler = $this->container->get('AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandler');
        $this->certificateReader = $this->container->get('AdyenPayment\Certificate\Filesystem\CertificateReader');

        $this->logger = $this->get('adyen_payment.logger');
    }

    public function importAction(): void
    {
        try {
            $importResult = ($this->applePayTransportHandler)(ApplePayCertificateRequest::create());

            $this->response->setHttpResponseCode(Response::HTTP_OK);
            $this->View()->assign('responseText', sprintf(
                'Imported %s Adyen ApplePay certificate successfully.',
                $importResult->usedFallback() ? 'fallback' : ''
            ));

        } catch (\Exception $exception) {

        }
    }
}
