<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\Connection\Request\ConnectionRequest;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Utilities\Request;

/**
 * Class Shopware_Controllers_Backend_AdyenValidateConnection
 *
 */
class Shopware_Controllers_Backend_AdyenValidateConnection extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     *
     * @throws Exception
     */
    public function validateAction(): void
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
        $result = AdminAPI::get()->testConnection($storeId)->test($connectionRequest);

        $this->returnAPIResponse($result);
    }
}
