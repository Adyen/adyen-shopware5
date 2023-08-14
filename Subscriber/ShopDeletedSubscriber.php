<?php

namespace AdyenPayment\Subscriber;

use Adyen\Core\BusinessLogic\Domain\Disconnect\Services\DisconnectService;
use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\ServiceRegister;
use Enlight\Event\SubscriberInterface;
use Exception;

class ShopDeletedSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Controllers_Backend_Config::deleteValuesAction::after' => 'disconnectOnShopDeletion'
        ];
    }

    /**
     * @return void
     */
    public function disconnectOnShopDeletion()
    {
        $params = Shopware()->Front()->Request()->getParams();

        if ($params['_repositoryClass'] !== 'shop' || $params['id'] === 0) {
            return;
        }

        if (!in_array($params['id'], $this->getStoreService()->getConnectedStores())) {
            return;
        }

        try {
            StoreContext::doWithStore(
                $params['id'],
                [$this->getDisconnectService(), 'disconnect']
            );
        } catch (Exception $e) {
            Logger::logError('Substore deleted. Failed to disconnect substore because ' . $e->getMessage());
        }
    }

    /**
     * @return DisconnectService
     */
    private function getDisconnectService(): DisconnectService
    {
        return ServiceRegister::getService(DisconnectService::class);
    }

    /**
     * @return StoreService
     */
    private function getStoreService(): StoreService
    {
        return ServiceRegister::getService(StoreService::class);
    }
}