<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

/**
 * Class Shopware_Controllers_Backend_AdyenOrderStatuses
 */
class Shopware_Controllers_Backend_AdyenOrderStatuses extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     *
     * @throws Exception
     */
    public function getOrderStatusesAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->store($storeId)->getStoreOrderStatuses();

        $this->returnAPIResponse($result);
    }
}
