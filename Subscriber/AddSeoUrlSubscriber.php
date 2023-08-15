<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Components\ApplePay\SeoUrlWriter;
use Enlight\Event\SubscriberInterface;

final class AddSeoUrlSubscriber implements SubscriberInterface
{
    /** @var SeoUrlWriter */
    private $seoUrlWriter;

    public function __construct(SeoUrlWriter $seoUrlWriter)
    {
        $this->seoUrlWriter = $seoUrlWriter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_CronJob_RefreshSeoIndex_CreateRewriteTable' => '__invoke',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Seo' => '__invoke'
        ];
    }

    public function __invoke(): void
    {
        ($this->seoUrlWriter)();
    }
}
