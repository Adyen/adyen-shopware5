<?php

declare(strict_types=1);

use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandlerInterface;

/**
 * Class Shopware_Controllers_Frontend_ApplePayCertificate
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Frontend_ApplePayCertificate extends Enlight_Controller_Action
{
    /**
     * @var ApplePayTransportHandlerInterface
     */
    private $applePayHandler;

    public function preDispatch()
    {
        // TODO injecteer ApplePayCertificateReader
//        $this->applePayHandler = $this->get('AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandler');
    }

    public function indexAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $this->Response()->setHeader('Content-Type', 'text/plain');

        // TODO roep $this->applePayCertificateReader op in setBody
        $this->Response()->setBody('Hallo');
    }
}

