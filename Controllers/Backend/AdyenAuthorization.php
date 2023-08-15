<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\EmptyConnectionDataException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\EmptyStoreException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidModeException;
use Adyen\Core\BusinessLogic\AdminAPI\Connection\Request\ConnectionRequest;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Utilities\Request;

/**
 * Class Shopware_Controllers_Backend_AdyenAuthorization
 */
class Shopware_Controllers_Backend_AdyenAuthorization extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     *
     * @throws EmptyConnectionDataException
     * @throws EmptyStoreException
     * @throws InvalidModeException
     */
    public function connectAction(): void
    {
        $requestData = Request::getPostData();
        $storeId = $this->Request()->get('storeId');
        $connectionRequest = new ConnectionRequest(
            $storeId,
            $requestData['mode'] ?? '',
            $requestData['testData']['apiKey'] ?? '',
            $requestData['testData']['merchantId'] ?? '',
            $requestData['liveData']['apiKey'] ?? '',
            $requestData['liveData']['merchantId'] ?? ''
        );

        $result = AdminAPI::get()->connection($storeId)->connect($connectionRequest);

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     */
    public function getConnectionSettingsAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->connection($storeId)->getConnectionSettings();

        $this->returnAPIResponse($result);
    }
}
