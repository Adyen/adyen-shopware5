<?php

class Shopware_Controllers_Frontend_ApplePayMerchantAssociation extends Enlight_Controller_Action
{
    public function indexAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'text/plain');
        fpassthru(fopen('https://eu.adyen.link/.well-known/apple-developer-merchantid-domain-association', 'rb'));
    }
}
