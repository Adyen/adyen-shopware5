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
//        $this->applePayHandler = $this->get('AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandler');
    }

    public function indexAction(): void
    {
        dd('apple pay certificate index');
//        $this->Request()->setHeader('Content-Type', 'text/plain');
//        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
//
//        ($this->applePayHandler)(ApplePayCertificateRequest::create());
    }
}

