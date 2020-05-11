<?php

namespace MeteorAdyen\Components\Payload\Providers;

use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;

/**
 * Class ShopperInfoProvider
 * @package MeteorAdyen\Components\Payload\Providers
 */
class ShopperInfoProvider implements PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array
    {
        return [
            'shopperIP' => $context->getShopperInfo()['shopperIP'],
            'shopperEmail' => $context->getOrder()->getCustomer()->getEmail(),
            'shopperName' => [
                'firstName' => $context->getOrder()->getCustomer()->getFirstname(),
                'lastName' => $context->getOrder()->getCustomer()->getLastname(),
                'gender' => $context->getOrder()->getCustomer()->getSalutation(),
            ],
            'shopperLocale' => Shopware()->Shop()->getLocale()->getLocale(),
            'shopperReference' => $context->getOrder()->getCustomer()->getId(),
            'countryCode' => $context->getOrder()->getBilling()->getCountry()->getIso(),
            'billingAddress' => [
                'city' => $context->getOrder()->getBilling()->getCity(),
                'country' => $context->getOrder()->getBilling()->getCountry()->getIso(),
                'houseNumberOrName' => $context->getOrder()->getBilling()->getNumber(),
                'postalCode' => $context->getOrder()->getBilling()->getZipCode(),
                'street' => $context->getOrder()->getBilling()->getStreet(),
            ],
        ];
    }
}
