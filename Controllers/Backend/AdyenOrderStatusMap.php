<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Utilities\Request;
use Adyen\Core\BusinessLogic\AdminAPI\OrderMappings\Request\OrderMappingsRequest;

/**
 * Class Shopware_Controllers_Backend_AdyenOrderStatusMap
 */
class Shopware_Controllers_Backend_AdyenOrderStatusMap extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     */
    public function getOrderStatusMapAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->orderMappings($storeId)->getOrderStatusMap();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     */
    public function putOrderStatusMapAction(): void
    {
        $requestData = Request::getPostData();
        $storeId = $this->Request()->get('storeId');
        $orderStatusMapRequest = OrderMappingsRequest::parse($requestData);

        $result = AdminAPI::get()->orderMappings($storeId)->saveOrderStatusMap($orderStatusMapRequest);

        $this->returnAPIResponse($result);
    }
}
