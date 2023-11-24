<?php

namespace AdyenPayment\E2ETest\Services;

/**
 * Class CreateSeedDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class BaseCreateSeedDataService
{
    const CREDIT_CARD = 'creditCard';
    const IDEAL = 'ideal';
    const KLARNA_PAY_NOW = 'klarnaPayNow';
    const KLARNA_PAY_LATER = 'klarnaPayLater';
    const KLARNA_PAY_OVERTIME = 'klarnaPayOverTime';
    const TWINT = 'twint';
    const BANCONTACT_MODILE = 'bancontact';
    const PAYPAL = 'payPal';
    const APPLE_PAY = 'applePay';

    /**
     * Reads from json file
     *
     * @return array
     */
    protected function readFromJSONFile(): array
    {
        $jsonString = file_get_contents(
            './custom/plugins/AdyenPayment/E2ETest/Data/test_data.json',
            FILE_USE_INCLUDE_PATH
        );

        return json_decode($jsonString, true);
    }
}