<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Components\Integration\FileService;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Utilities\Request;
use Adyen\Core\BusinessLogic\AdminAPI\AdyenGivingSettings\Request\AdyenGivingSettingsRequest;

/**
 * Class AdyenGivingSettings
 */
class Shopware_Controllers_Backend_AdyenGivingSettings extends Enlight_Controller_Action
{
    use AjaxResponseSetter {
        AjaxResponseSetter::preDispatch as protected ajaxResponseSetterPreDispatch;
    }

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->ajaxResponseSetterPreDispatch();
        $this->fileService = $this->get(FileService::class);
    }

    /**
     * @return void
     */
    public function getAdyenGivingSettingsAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->adyenGivingSettings($storeId)->getAdyenGivingSettings();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     */
    public function putAdyenGivingSettingsAction(): void
    {
        $requestData = $this->Request()->getParams();
        $storeId = $requestData['storeId'];

        $result = AdminAPI::get()->adyenGivingSettings($storeId)->saveAdyenGivingSettings($this->createGivingRequest($storeId));

        $this->returnAPIResponse($result);
    }

    private function createGivingRequest(string $storeId): AdyenGivingSettingsRequest
    {
        $requestData = $this->Request()->getParams();

        if ($requestData['enableAdyenGiving'] === 'false') {
            $this->fileService->delete('adyen-giving-logo-store-' . $storeId);
            $this->fileService->delete('adyen-giving-background-store-' . $storeId);

            return new AdyenGivingSettingsRequest(false);
        }

        $this->saveImages($storeId);

        return new AdyenGivingSettingsRequest(
            $requestData['enableAdyenGiving'] === 'true',
            $requestData['charityName'] ?? '',
            $requestData['charityDescription'] ?? '',
            $requestData['charityMerchantAccount'] ?? '',
            $requestData['donationAmount'] ?? '',
            $requestData['charityWebsite'] ?? '',
            $this->fileService->getLogoUrl('adyen-giving-logo-store-' . $storeId) ?? '',
            $this->fileService->getLogoUrl('adyen-giving-background-store-' . $storeId) ?? ''
        );
    }

    private function saveImages(string $storeId): void
    {
        $logo = $this->Request()->files->get('logo');
        $logoContents = null;

        if ($logo) {
            $filePath = (string)$logo->getRealPath();
            $stream = fopen($filePath, 'rb');
            $logoContents = stream_get_contents($stream);
        }

        if ($logoContents) {
            $this->fileService->write($logoContents, 'adyen-giving-logo-store-' . $storeId);
        }

        $backgroundImage = $this->Request()->files->get('backgroundImage');
        $imageContents = null;

        if ($backgroundImage) {
            $filePath = (string)$backgroundImage->getRealPath();
            $stream = fopen($filePath, 'rb');
            $imageContents = stream_get_contents($stream);
        }

        if ($imageContents) {
            $this->fileService->write($imageContents, 'adyen-giving-background-store-' . $storeId);
        }
    }
}
