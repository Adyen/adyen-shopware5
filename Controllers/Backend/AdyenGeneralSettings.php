<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\GeneralSettings\Request\GeneralSettingsRequest;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidCaptureDelayException;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidCaptureTypeException;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Exceptions\InvalidRetentionPeriodException;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Utilities\Request;

/**
 * Class AdyenGeneralSettings
 */
class Shopware_Controllers_Backend_AdyenGeneralSettings extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     */
    public function getGeneralSettingsAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->generalSettings($storeId)->getGeneralSettings();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws InvalidCaptureDelayException
     * @throws InvalidRetentionPeriodException
     * @throws InvalidCaptureTypeException
     */
    public function putGeneralSettingsAction(): void
    {
        $requestData = Request::getPostData();
        $storeId = $this->Request()->get('storeId');
        $generalSettingsRequest = new GeneralSettingsRequest(
            $requestData['basketItemSync'] ?? false,
            $requestData['capture'] ?? '',
            $requestData['captureDelay'] ?? 1,
            $requestData['shipmentStatus'] ?? '',
            $requestData['retentionPeriod'] ?? '',
            $requestData['enablePayByLink'] ?? false,
            $requestData['payByLinkTitle'] ?? '',
            $requestData['defaultLinkExpirationTime'] ?? '7'
        );

        $result = AdminAPI::get()->generalSettings($storeId)->saveGeneralSettings($generalSettingsRequest);

        $this->returnAPIResponse($result);
    }
}
