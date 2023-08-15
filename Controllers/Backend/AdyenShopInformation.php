<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\Stores\Response\StoreResponse;
use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

/**
 * Class Shopware_Controllers_Backend_AdyenShopInformation
 */
class Shopware_Controllers_Backend_AdyenShopInformation extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     */
    public function getStoresAction(): void
    {
        $result = AdminAPI::get()->store('')->getStores();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     */
    public function getCurrentStoreAction(): void
    {
        $sessionStoreId = Shopware()->BackendSession()->get('adyenStoreId');

        if ($sessionStoreId) {
            Shopware()->BackendSession()->remove('adyenStoreId');
            $this->returnAPIResponse(
                new StoreResponse(
                    $this->getStoreService()->getStoreById($sessionStoreId)
                )
            );
        }

        $result = AdminAPI::get()->store('')->getCurrentStore();

        $this->returnAPIResponse($result);
    }

    /**
     * @return StoreService
     */
    private function getStoreService(): StoreService
    {
        return ServiceRegister::getService(StoreService::class);
    }
}
