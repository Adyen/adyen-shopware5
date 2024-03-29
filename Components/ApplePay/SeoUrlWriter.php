<?php

declare(strict_types=1);

namespace AdyenPayment\Components\ApplePay;

use Shopware_Components_Modules;

final class SeoUrlWriter
{
    /** @var Shopware_Components_Modules */
    private $modules;

    public function __construct(Shopware_Components_Modules $modules)
    {
        $this->modules = $modules;
    }

    public function __invoke(): void
    {
        $this->modules->RewriteTable()->sInsertUrl(
            'sViewport=applepaymerchantassociation',
            '.well-known/apple-developer-merchantid-domain-association'
        );
    }
}
