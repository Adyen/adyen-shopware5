<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\Builder;

final class PaymentMethodOptionsBuilder implements PaymentMethodOptionsBuilderInterface
{
    public function __invoke(): array
    {
        $countryCode = (string) (Shopware()->Session()->sOrderVariables['sUserData']['additional']['country']['countryiso'] ?? '');
        if (!$countryCode) {
            $countryCode = (string) (Shopware()->Modules()->Admin()->sGetUserData()['additional']['country']['countryiso']);
        }

        $currencyName = Shopware()->Session()->sOrderVariables['sBasket']['sCurrencyName'] ?? '';
        $currency = $currencyName ?: Shopware()->Shop()->getCurrency()->getCurrency();

        $value = (float) (
            Shopware()->Session()->sOrderVariables['sBasket']['AmountNumeric']
            ?? Shopware()->Modules()->Basket()->sGetAmount()['totalAmount']
            ?? 1.0
        );

        $paymentMethodOptions['countryCode'] = $countryCode;
        $paymentMethodOptions['currency'] = $currency;
        $paymentMethodOptions['value'] = $value;

        return $paymentMethodOptions;
    }
}
