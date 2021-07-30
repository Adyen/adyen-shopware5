<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

/**
 * Class PaymentMethodService
 * @package AdyenPayment\Components
 */
class PaymentMethodService
{
    /**
     * PaymentMethodService constructor.
     */
    public function __construct()
    {
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
