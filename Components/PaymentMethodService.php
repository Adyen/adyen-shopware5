<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

/**
 * Class PaymentMethodService
 * @package AdyenPayment\Components
 */
class PaymentMethodService
{
    const PM_LOGO_FILENAME = [
        'scheme' => 'card',
        'yandex_money' => 'yandex'
    ];

    /**
     * PaymentMethodService constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $type
     * @return string
     */
    public function getAdyenImageByType($type)
    {
        //Some payment method codes don't match the logo filename
        if (!empty(self::PM_LOGO_FILENAME[$type])) {
            $type = self::PM_LOGO_FILENAME[$type];
        }
        return sprintf('https://checkoutshopper-live.adyen.com/checkoutshopper/images/logos/%s.svg', $type);
    }

    public function getPaymentMethodOptions()
    {
        $countryCode = Shopware()->Session()->sOrderVariables['sUserData']['additional']['country']['countryiso'];
        if (!$countryCode) {
            $countryCode = Shopware()->Modules()->Admin()->sGetUserData()['additional']['country']['countryiso'];
        }

        $currency = Shopware()->Session()->sOrderVariables['sBasket']['sCurrencyName'];
        if (!$currency) {
            $currency = Shopware()->Shop()->getCurrency()->getCurrency();
        }

        $value = Shopware()->Session()->sOrderVariables['sBasket']['AmountNumeric'];
        if (!$value) {
            $value = Shopware()->Modules()->Basket()->sGetAmount()['totalAmount'];
        }

        $paymentMethodOptions['countryCode'] = $countryCode;
        $paymentMethodOptions['currency'] = $currency;
        $paymentMethodOptions['value'] = $value ?? 1;

        return $paymentMethodOptions;
    }
}
