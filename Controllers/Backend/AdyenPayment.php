<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\Payment\Request\PaymentMethodRequest;
use Adyen\Core\BusinessLogic\Domain\Payment\Exceptions\PaymentMethodDataEmptyException;
use AdyenPayment\Components\Integration\FileService;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

class Shopware_Controllers_Backend_AdyenPayment extends Enlight_Controller_Action
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
     *
     * @throws Exception
     */
    public function getAvailableMethodsAction(): void
    {
        $storeId = $this->Request()->get('storeId');

        $result = AdminAPI::get()->payment($storeId)->getAvailablePaymentMethods();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function getConfiguredMethodsAction(): void
    {
        $storeId = $this->Request()->get('storeId');

        $result = AdminAPI::get()->payment($storeId)->getConfiguredPaymentMethods();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function getMethodByIdAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $id = $this->Request()->get('methodId');

        $result = AdminAPI::get()->payment($storeId)->getMethodById($id);

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws PaymentMethodDataEmptyException
     * @throws Exception
     */
    public function saveMethodAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $method = $this->createPaymentMethodRequest();

        $result = AdminAPI::get()->payment($storeId)->saveMethodConfiguration($method);

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws PaymentMethodDataEmptyException
     * @throws Exception
     */
    public function updateMethodAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $method = $this->createPaymentMethodRequest();

        $result = AdminAPI::get()->payment($storeId)->updateMethodConfiguration($method);

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function deleteMethodAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $methodId = $this->Request()->get('methodId');

        $result = AdminAPI::get()->payment($storeId)->deletePaymentMethodById($methodId);
        $this->fileService->delete($methodId . '_store_' . $storeId);

        $this->returnAPIResponse($result);
    }

    /**
     * @return PaymentMethodRequest
     *
     * @throws PaymentMethodDataEmptyException
     * @throws Exception
     */
    private function createPaymentMethodRequest(): PaymentMethodRequest
    {
        $requestData = $this->Request()->getParams();
        $logo = $this->Request()->files->get('logo');
        $logoContents = null;

        if ($logo) {
            $filePath = (string)$logo->getRealPath();
            $stream = fopen($filePath, 'rb');
            $logoContents = stream_get_contents($stream);
        }

        if ($logoContents) {
            $this->fileService->write($logoContents, $requestData['methodId'] . '_store_' . $requestData['storeId']);
        }

        if (!isset($requestData['currencies']) || $requestData['currencies'] === '') {
            $requestData['currencies'] = [];
        }

        if ($requestData['currencies'] === 'ANY') {
            $requestData['currencies'] = ['ANY'];
        }

        if (is_string($requestData['currencies'])) {
            $requestData['currencies'] = [$requestData['currencies']];
        }

        if (!isset($requestData['countries']) || $requestData['countries'] === '') {
            $requestData['countries'] = [];
        }

        if ($requestData['countries'] === 'ANY') {
            $requestData['countries'] = ['ANY'];
        }

        $requestData['additionalData'] = !empty($requestData['additionalData']) ?
            json_decode($requestData['additionalData'], true) : [];
        $requestData['logo'] = $this->fileService->getLogoUrl($requestData['methodId'] . '_store_' . $requestData['storeId']);

        return PaymentMethodRequest::parse($requestData);
    }
}
