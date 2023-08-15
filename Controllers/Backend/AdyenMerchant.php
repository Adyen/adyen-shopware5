<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

/**
 * Class Shopware_Controllers_Backend_AdyenMerchant
 */
class Shopware_Controllers_Backend_AdyenMerchant extends Enlight_Controller_Action
{
	use AjaxResponseSetter;

    /**
     * @return void
     *
     * @throws Exception
     */
	public function indexAction(): void
	{
		$storeId = $this->Request()->get('storeId');
		$result  = AdminAPI::get()->merchant($storeId)->getMerchants();

		$this->returnAPIResponse($result);
	}
}
