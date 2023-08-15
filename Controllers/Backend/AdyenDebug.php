<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Utilities\Request;

/**
 * Class Shopware_Controllers_Backend_AdyenDebug
 */
class Shopware_Controllers_Backend_AdyenDebug extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     */
    public function getDebugModeAction(): void
    {
        $result = AdminAPI::get()->debug()->getDebugMode();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     */
    public function setDebugModeAction(): void
    {
        $requestData = Request::getPostData();
        $result = AdminAPI::get()->debug()->setDebugMode($requestData['debugMode'] ?? false);

        $this->returnAPIResponse($result);
    }
}
