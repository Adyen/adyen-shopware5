<?php

declare(strict_types=1);

class Shopware_Controllers_Backend_ApplePayCertificate extends Shopware_Controllers_Backend_ExtJs
{
    public function generateSeoUrlAction()
    {
        $shopId = $this->Request()->getParam('shopId');

        $offset = $this->Request()->getParam('offset');
        $limit = $this->Request()->getParam('limit', 50);

        /** @var Shopware_Components_SeoIndex $seoIndex */
        $seoIndex = $this->container->get('seoindex');
        $seoIndex->registerShop($shopId);

        /** @var sRewriteTable $rewriteTableModule */
        $rewriteTableModule = $this->container->get('modules')->RewriteTable();
        $rewriteTableModule->baseSetup();
        $rewriteTableModule->sInsertUrl(
            'sViewport=applepaycertificate',
            '.well-known/apple-developer-merchantid-domain-association'
        );

        $this->View()->assign(['success' => true]);
    }
}
