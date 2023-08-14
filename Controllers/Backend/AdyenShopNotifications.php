<?php

use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Services\ShopNotificationService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Components\LastOpenTimeService;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Entities\LastOpenTime;

class Shopware_Controllers_Backend_AdyenShopNotifications extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @var ShopNotificationService
     */
    private $notificationService;
    /**
     * @var LastOpenTimeService
     */
    private $openTimeService;

    public function getAction()
    {
        $storeIds = $this->getConnectedStores();
        $result = [];
        $lastOpenTime = $this->getOpenTimeService()->getLastOpenTime();

        foreach ($storeIds as $storeId) {
            $hasNotifications = $this->storeHasNotifications($storeId, $lastOpenTime);

            if (!$hasNotifications) {
                continue;
            }

            $store = $this->getStoreService()->getStoreById($storeId);

            if (!$store) {
                continue;
            }

            $result[] = [
                'storeId' => $storeId,
                'storeName' => $store->getStoreName(),
            ];
        }

        $this->getOpenTimeService()->saveLastOpenTime(new DateTime());

        $this->Response()->setBody(json_encode($result));
    }

    /**
     * @param string $storeId
     * @param DateTime $lastOpenTime
     *
     * @return bool
     *
     * @throws Exception
     */
    private function storeHasNotifications(string $storeId, DateTime $lastOpenTime): bool
    {
        return StoreContext::doWithStore(
            $storeId,
            [$this->getNotificationService(), 'hasSignificantNotifications'],
            [$lastOpenTime]
        );
    }

    /**
     * @return array
     */
    private function getConnectedStores(): array
    {
        return $this->getStoreService()->getConnectedStores();
    }

    /**
     * @return ShopNotificationService
     */
    private function getNotificationService(): ShopNotificationService
    {
        if ($this->notificationService === null) {
            $this->notificationService = ServiceRegister::getService(ShopNotificationService::class);
        }

        return $this->notificationService;
    }

    /**
     * @return StoreService
     */
    private function getStoreService(): StoreService
    {
        return ServiceRegister::getService(StoreService::class);
    }

    /**
     * @return LastOpenTimeService
     */
    private function getOpenTimeService(): LastOpenTimeService
    {
        if ($this->openTimeService === null) {
            $this->openTimeService = ServiceRegister::getService(LastOpenTimeService::class);
        }

        return $this->openTimeService;
    }
}
