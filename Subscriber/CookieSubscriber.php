<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Bundle\CookieBundle\CookieCollection;
use Shopware\Bundle\CookieBundle\Structs\CookieGroupStruct;
use Shopware\Bundle\CookieBundle\Structs\CookieStruct;

class CookieSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'CookieCollector_Collect_Cookies' => 'addAdyenCookie',
        ];
    }

    public function addAdyenCookie(): CookieCollection
    {
        return new CookieCollection([
            new CookieStruct(
                'comfort',
                '/^adyen/',
                'Adyen Cookies',
                CookieGroupStruct::TECHNICAL
            ),
        ]);
    }
}
