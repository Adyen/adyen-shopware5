<?php

namespace AdyenPayment\Subscriber;

use Adyen\Core\BusinessLogic\TransactionLog\Services\TransactionLogService;
use Adyen\Core\Infrastructure\ServiceRegister;
use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;

class OrderListHandler implements SubscriberInterface
{
    /**
     * @var TransactionLogService
     */
    private $logService;

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Controllers_Backend_Order::getList::after' => 'extendOrderList',
        ];
    }

    public function extendOrderList(Enlight_Hook_HookArgs $args): void
    {
        $return = $args->getReturn();

        foreach ($return['data'] as $index => $order) {
            $log = $this->getLogService()->findByMerchantReference($order['temporaryId']);

            if (!$log) {
                continue;
            }

            $return['data'][$index]['adyenPspReference'] = $log->getPspReference();
            $return['data'][$index]['adyenPaymentMethod'] = $log->getPaymentMethod();
        }

        $args->setReturn($return);
    }

    /**
     * @return TransactionLogService
     */
    private function getLogService(): TransactionLogService
    {
        if ($this->logService === null) {
            $this->logService = ServiceRegister::getService(TransactionLogService::class);
        }

        return $this->logService;
    }
}
