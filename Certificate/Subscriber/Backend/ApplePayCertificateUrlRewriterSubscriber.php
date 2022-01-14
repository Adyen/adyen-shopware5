<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;

final class ApplePayCertificateUrlRewriterSubscriber implements SubscriberInterface
{
    private \Shopware_Components_Modules $modules;

    public function __construct(\Shopware_Components_Modules $modules)
    {
        $this->modules = $modules;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_CronJob_RefreshSeoIndex_CreateRewriteTable' => 'createApplePayCertificateRewriteTable',
            'sRewriteTable::sCreateRewriteTable::after' => 'createApplePayCertificateRewriteTable',
        ];
    }

    public function createApplePayCertificateRewriteTable(): void
    {
        /** @var \sRewriteTable $rewriteTableModule */
        $rewriteTableModule = $this->modules->RewriteTable();
        $rewriteTableModule->sInsertUrl(
            'sViewport=applepaycertificate',
            'well-known/apple-developer-merchantid-domain-association'
        );
    }
}
