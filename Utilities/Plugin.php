<?php

namespace AdyenPayment\Utilities;

/**
 * Class Plugin
 *
 * @package AdyenPayment\Utilities
 */
class Plugin
{
    private const ADYEN_PAYMENTS_PREFIX = 'adyen_';

    /**
     * Retrieves plugin version.
     *
     * @return string
     */
    public static function getVersion(): string
    {
        $config = simplexml_load_string(file_get_contents(__DIR__ . '/../plugin.xml'));
        $config = json_decode(json_encode($config), true);

        return !empty($config['version']) ? $config['version'] : '';
    }

    /**
     * Determines if Shopware payment mean name is Adyen payment mean
     *
     * @param string $paymentMeanName
     * @return bool
     */
    public static function isAdyenPaymentMean(string $paymentMeanName): bool
    {
        $adyenPaymentsPrefix = self::ADYEN_PAYMENTS_PREFIX;

        return 0 === strncmp($paymentMeanName, $adyenPaymentsPrefix, strlen($adyenPaymentsPrefix));
    }

    /**
     * Transforms Adyen payment mean name into the Adyen payment type
     *
     * @param string $paymentMeanName
     * @return string
     */
    public static function getAdyenPaymentType(string $paymentMeanName): string
    {
        return (string)str_replace(self::ADYEN_PAYMENTS_PREFIX, '', $paymentMeanName);
    }

    /**
     * Gets Shopware payment mean name from given Adyen payment method code
     *
     * @param string $adyenPaymentMethodCode
     * @return string
     */
    public static function getPaymentMeanName(string $adyenPaymentMethodCode): string
    {
        return self::ADYEN_PAYMENTS_PREFIX . $adyenPaymentMethodCode;
    }
}
