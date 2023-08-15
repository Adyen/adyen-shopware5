<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

class Shopware_Controllers_Backend_AdyenVersion extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    public function getVersionAction(): void
    {
        $result = AdminAPI::get()->versions()->getVersionInfo();

        $this->returnAPIResponse($result);
    }
}
