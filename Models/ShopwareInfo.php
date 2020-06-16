<?php

namespace AdyenPayment\Models;

use AdyenPayment\AdyenPayment;
use Shopware;

class ShopwareInfo
{
    const INTEGRATOR = 'Adyen';
    const SHOPWARE = 'Shopware';

    /**
     * @return string
     */
    public function getIntegrator(): string
    {
        return self::INTEGRATOR;
    }

    /**
     * @return mixed
     */
    public function getShopwareVersion()
    {
        return Shopware::VERSION;
    }

    /**
     * @return mixed
     */
    public function getShopwareName()
    {
        return self::SHOPWARE;
    }

    /**
     * @return mixed
     */
    public function getPluginVersion()
    {
        return "1.4.1";
    }

    /**
     * @return mixed
     */
    public function getPluginName()
    {
        return AdyenPayment::NAME;
    }
}
