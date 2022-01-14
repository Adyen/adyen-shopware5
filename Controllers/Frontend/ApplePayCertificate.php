<?php

declare(strict_types=1);

use AdyenPayment\Certificate\Filesystem\CertificateReaderInterface;
use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandlerInterface;

/**
 * Class Shopware_Controllers_Frontend_ApplePayCertificate
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Frontend_ApplePayCertificate extends Enlight_Controller_Action
{
    /**
     * @var CertificateReaderInterface
     */
    private $certificateReader;

    public function preDispatch()
    {
        $this->certificateReader = $this->get('AdyenPayment\Certificate\Filesystem\CertificateReader');
    }

    public function indexAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $this->Response()->setHeader('Content-Type', 'text/plain');
        $this->Response()->setBody(($this->certificateReader)()->certificate());
    }
}

