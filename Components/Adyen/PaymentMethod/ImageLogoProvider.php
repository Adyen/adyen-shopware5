<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

class ImageLogoProvider implements ImageLogoProviderInterface
{
    const PM_LOGO_FILENAME = [
        'scheme' => 'card',
        'yandex_money' => 'yandex'
    ];

    /**
     * @param $type
     * @return string
     */
    public function getAdyenImageByType($type): string
    {
        //Some payment method codes don't match the logo filename
        if (!empty(self::PM_LOGO_FILENAME[$type])) {
            $type = self::PM_LOGO_FILENAME[$type];
        }
        return sprintf('https://checkoutshopper-live.adyen.com/checkoutshopper/images/logos/%s.svg', $type);
    }
}
