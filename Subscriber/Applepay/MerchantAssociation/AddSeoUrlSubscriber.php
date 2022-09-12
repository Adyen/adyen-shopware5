<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Applepay\MerchantAssociation;

use AdyenPayment\Applepay\MerchantAssociation\RewriteUrl\UrlWriter;
use Enlight\Event\SubscriberInterface;

final class AddSeoUrlSubscriber implements SubscriberInterface
{
    /** @var UrlWriter */
    private $seoUrlWriter;

    public function __construct(UrlWriter $seoUrlWriter)
    {
        $this->seoUrlWriter = $seoUrlWriter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_CronJob_RefreshSeoIndex_CreateRewriteTable' => '__invoke',
            'sRewriteTable::sCreateRewriteTable::after' => '__invoke',
        ];
    }

    public function __invoke(): void
    {
        ($this->seoUrlWriter)();
    }
}
