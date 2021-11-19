<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

final class ImageLogoProvider implements ImageLogoProviderInterface
{
    public const PM_LOGO_FILENAME = [
        'scheme' => 'card',
        'yandex_money' => 'yandex',
    ];

    public function provideByType(string $type): string
    {
        //Some payment method codes don't match the logo filename
        $logoType = self::PM_LOGO_FILENAME[$type] ?? $type;

        return sprintf('https://checkoutshopper-live.adyen.com/checkoutshopper/images/logos/%s.svg', $logoType);
    }
}
