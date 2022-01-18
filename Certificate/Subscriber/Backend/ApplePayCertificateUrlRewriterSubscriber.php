<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;

final class ApplePayCertificateUrlRewriterSubscriber implements SubscriberInterface
{
    private \Shopware_Components_Modules $modules;

    public function __construct(
        \Shopware_Components_Modules $modules
    ) {
        $this->modules = $modules;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_CronJob_RefreshSeoIndex_CreateRewriteTable' => 'createApplePayCertificateRewriteTable',
            'sRewriteTable::sCreateRewriteTable::after' => 'createApplePayCertificateRewriteTable',
            'Enlight_Controller_Action_PostDispatch_Backend_Performance' => 'loadPerformanceExtension',
            'Shopware_Controllers_Seo_filterCounts' => 'addApplePayUrlCount',
        ];
    }

    public function createApplePayCertificateRewriteTable(): void
    {
        /** @var \sRewriteTable $rewriteTableModule */
        $rewriteTableModule = $this->modules->RewriteTable();
        $rewriteTableModule->sInsertUrl(
            'sViewport=applepaycertificate',
            '.well-known/apple-developer-merchantid-domain-association'
        );
    }

    public function loadPerformanceExtension(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        $request = $subject->Request();

        if ('load' !== $request->getActionName()) {
            return;
        }

        $subject->View()->addTemplateDir($this->getPath().'/Resources/views/');
        $subject->View()->extendsTemplate('backend/performance/view/applepaycertificate.js');
    }

    public function addApplePayUrlCount(\Enlight_Event_EventArgs $args)
    {
        $counts = $args->getReturn();

        //Currently, there's only a single URL to be generated for each shop, so we'll just return a static 1.
        $counts['applepaycertificate'] = 1;

        return $counts;
    }

    /**
     * {@inheritdoc}
     */
    private function getPath()
    {
        if (null === $this->path) {
            $reflected = new \ReflectionObject($this);
            $this->path = \dirname($reflected->getFileName());
        }

        return $this->path;
    }
}
