<?php

namespace AdyenPayment\E2ETest\Services;

/**
 * Class CreateSeedDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class BaseCreateSeedDataService
{
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