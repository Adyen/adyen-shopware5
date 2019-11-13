<?php

namespace MeteorAdyen\Models;

use MeteorAdyen\MeteorAdyen;
use Shopware;

class ShopwareInfo
{
    const INTEGRATOR = 'Meteor';
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
        return "1.0.0";
    }

    /**
     * @return mixed
     */
    public function getPluginName()
    {
        return MeteorAdyen::NAME;
    }
}
