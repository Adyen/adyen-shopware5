<?php

namespace AdyenPayment\Subscriber;

use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Services\GeneralSettingsService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\BusinessLogic\Webhook\Services\OrderStatusMappingService;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Webhook\PaymentStates;
use AdyenPayment\Utilities\Plugin;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Exception;
use Shopware_Controllers_Backend_Order;

class BackendOrderSubscriber implements SubscriberInterface
{
    /**
     * @var TransactionHistoryService
     */
    private $historyService;

    /**
     * @var OrderStatusMappingService
     */
    private $orderStatusMappingService;

    /**
     * @var GeneralSettingsService
     */
    private $generalSettingsService;

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

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws Exception
     */
    private function addData(array &$data): void
    {
        $merchantReferences = [];
        $adyenOrders = [];

        foreach ($data as &$order) {
            $order['adyenDisplayPaymentLink'] = false;
            if ((!isset($order['payment']['name']) || !Plugin::isAdyenPaymentMean($order['payment']['name']))) {
                $orderStatusMapping = StoreContext::doWithStore(
                    (string)$order['shopId'],
                    [$this->getStatusMappingService(), 'getOrderStatusMappingSettings']
                );
                $orderStatusId = (string)$order['paymentStatus']['id'] ?? '';
                $generalSettings = StoreContext::doWithStore(
                    (string)$order['shopId'],
                    [$this->getGeneralSettingsService(), 'getGeneralSettings']
                );

                $isPaymentLinkEnabled = $generalSettings && $generalSettings->isEnablePayByLink();
                if ($orderStatusId === (string)$orderStatusMapping[PaymentStates::STATE_CANCELLED] ||
                    $orderStatusId === (string)$orderStatusMapping[PaymentStates::STATE_FAILED] ||
                    $orderStatusId === (string)$orderStatusMapping[PaymentStates::STATE_NEW]) {
                    $order['adyenDisplayPaymentLink'] = true;
                    $order['adyenPaymentLinkEnabled'] = $isPaymentLinkEnabled;
                }

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

    /**
     * @return OrderStatusMappingService
     */
    private function getStatusMappingService(): OrderStatusMappingService
    {
        if ($this->orderStatusMappingService === null) {
            $this->orderStatusMappingService = ServiceRegister::getService(OrderStatusMappingService::class);
        }

        return $this->orderStatusMappingService;
    }

    /**
     * @return GeneralSettingsService
     */
    private function getGeneralSettingsService(): GeneralSettingsService
    {
        if ($this->generalSettingsService === null) {
            $this->generalSettingsService = ServiceRegister::getService(GeneralSettingsService::class);
        }

        return $this->generalSettingsService;
    }
}
