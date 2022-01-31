<?php

declare(strict_types=1);

use AdyenPayment\Applepay\MerchantAssociation\RewriteUrl\SeoUrlWriter;
use AdyenPayment\Applepay\MerchantAssociation\RewriteUrl\UrlWriter;

class Shopware_Controllers_Backend_RegisterApplePayAssociationUrl extends Shopware_Controllers_Backend_ExtJs
{
    private Shopware_Components_SeoIndex $seoIndexer;
    private Shopware_Components_Modules $modules;
    private UrlWriter $seoUrlWriter;

    public function preDispatch(): void
    {
        parent::preDispatch();

        $this->seoIndexer = $this->container->get('seoindex');
        $this->modules = $this->container->get('modules');
        $this->seoUrlWriter = $this->container->get(SeoUrlWriter::class);
    }

    public function registerAction(): void
    {
        $this->seoIndexer->registerShop($this->Request()->getParam('shopId'));
        ($this->seoUrlWriter)();

        $this->View()->assign(['success' => true]);
    }
}