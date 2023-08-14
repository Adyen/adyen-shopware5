<?php

namespace AdyenPayment\Controllers\Common;

use Adyen\Core\BusinessLogic\AdminAPI\Response\Response;
use Enlight_Exception;
use Exception;

trait AjaxResponseSetter
{
	/**
	 * @return void
	 *
	 * @throws Enlight_Exception|Exception
	 */
	public function preDispatch(): void
	{
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
	}

	/**
	 * @param Response $response
	 *
	 * @return void
	 */
	public function returnAPIResponse(Response $response): void
	{
        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(
            json_encode($response->toArray())
        );

		if (!$response->isSuccessful()) {
			$this->Response()->setHttpResponseCode($response->getStatusCode());
		}
	}
}
