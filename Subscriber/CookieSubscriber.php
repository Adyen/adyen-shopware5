<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Bundle\CookieBundle\CookieCollection;
use Shopware\Bundle\CookieBundle\Structs\CookieGroupStruct;
use Shopware\Bundle\CookieBundle\Structs\CookieStruct;

final class CookieSubscriber implements SubscriberInterface
{
    /** @var string[] */
    private const ADYEN_COOKIE_NAMES = [
        '_rp_uid',
        'JSESSIONID',
        '_uetvid',
        '_uetsid',
        'datadome',
        'rl_anonymous_id',
        '_mkto_trk',
        'rl_user_id',
        '_hjid',
        'lastUpdatedGdpr',
        'gdpr',
        '_fbp',
        '_ga',
        '_gid',
        '_gcl_au',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            'CookieCollector_Collect_Cookies' => 'addAdyenCookie',
        ];
    }

    /**
     * Returns a CookieCollection object containing cookies to be added to the consent manager.
     *
     * @return CookieCollection
     */
    public function addAdyenCookie(): CookieCollection
    {
        $collection = new CookieCollection();

        foreach (self::ADYEN_COOKIE_NAMES as $adyenCookieName) {
            $collection->add(new CookieStruct(
                $adyenCookieName,
                "/^$adyenCookieName$/",
                'Adyen '.$adyenCookieName,
                CookieGroupStruct::TECHNICAL
            ));
        }

        return $collection;
    }
}
