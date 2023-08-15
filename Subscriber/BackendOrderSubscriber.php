<?php

namespace AdyenPayment\Subscriber;

use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Utilities\Plugin;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware_Controllers_Backend_Order;

class BackendOrderSubscriber implements SubscriberInterface
{
    /**
     * @var TransactionHistoryService
     */
    private $historyService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'addTransactionData'];
    }

    public function addTransactionData(Enlight_Event_EventArgs $args): void
    {
        /** @var Shopware_Controllers_Backend_Order $subject */
        $subject = $args->getSubject();

        if ('getList' !== $subject->Request()->getActionName()) {
            return;
        }

        $data = $subject->View()->getAssign('data');

        $this->addData($data);

        $subject->View()->assign('data', $data);
    }

    private function addData(array &$data): void
    {
        $merchantReferences = [];
        $adyenOrders = [];

        foreach ($data as &$order) {
            if (!isset($order['payment']['name']) || !Plugin::isAdyenPaymentMean($order['payment']['name'])) {
                continue;
            }

            $merchantReferences[$order['shopId']][] = $order['temporaryId'];
            $adyenOrders[$order['shopId']][] = &$order;
        }

        unset($order);

        foreach ($merchantReferences as $storeId => $references) {
            $historyItems = StoreContext::doWithStore(
                $storeId,
                [$this->getService(), 'getTransactionHistoriesByReferences'],
                [$references]
            );

            foreach ($adyenOrders[$storeId] as &$order) {
                $order['adyenTransaction'] = false;

                foreach ($historyItems as $item) {
                    if ($item->getMerchantReference() !== $order['temporaryId']) {
                        continue;
                    }

                    $order['adyenTransaction'] = $item->isLive() !== null;
                    break;
                }
            }

            unset($order);
        }
    }

    /**
     * @return TransactionHistoryService
     */
    private function getService(): TransactionHistoryService
    {
        if ($this->historyService === null) {
            $this->historyService = ServiceRegister::getService(TransactionHistoryService::class);
        }

        return $this->historyService;
    }
}
