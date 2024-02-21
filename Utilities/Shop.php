<?php

namespace AdyenPayment\Utilities;

/**
 * Class Shop
 *
 * @package AdyenPayment\Utilities
 */
class Shop
{
    /**
     * Returns current shop id. In case of language shop, main shop id is fetched.
     *
     * @return int
     */
    public static function getShopId(): int
    {
        return Shopware()->Shop()->getMain() ? Shopware()->Shop()->getMain()->getId() : Shopware()->Shop()->getId();
    }
}
