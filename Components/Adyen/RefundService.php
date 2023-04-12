<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\Modification;
use AdyenPayment\AdyenApi\HttpClient\ClientMemoise;
use AdyenPayment\Components\NotificationManager;
use AdyenPayment\Models\Notification;
use AdyenPayment\Models\Refund;
use Doctrine\ORM\NonUniqueResultException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

class RefundService
{
    /** @var ClientMemoise */
    private $apiClientMap;

    /** @var ModelManager */
    private $modelManager;

    /** @var NotificationManager */
    private $notificationManager;

    public function __construct(
        ClientMemoise $apiClientMap,
        ModelManager $modelManager,
        NotificationManager $notificationManager
    ) {
        $this->apiClientMap = $apiClientMap;
        $this->modelManager = $modelManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @throws AdyenException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doRefund(int $orderId): Refund
    {
        /** @var Order $order */
        $order = $this->modelManager->find(Order::class, $orderId);
        $modification = new Modification(
            $this->apiClientMap->lookup($order->getShop())
        );

        $notification = $this->provideNotification($orderId);
        $request = [
            'originalReference' => $notification->getPspReference(),
            'modificationAmount' => [
                'value' => $notification->getAmountValue(),
                'currency' => $notification->getAmountCurrency(),
            ],
            'merchantAccount' => $notification->getMerchantAccountCode(),
        ];
        $refund = $modification->refund($request);

        $orderRefund = new Refund();
        $orderRefund->setOrderId($orderId);
        $orderRefund->setCreatedAt(new \DateTime());
        $orderRefund->setUpdatedAt(new \DateTime());
        $orderRefund->setPspReference($refund['pspReference']);
        $this->modelManager->persist($orderRefund);
        $this->modelManager->flush();

        return $orderRefund;
    }

    /**
     * @throws NonUniqueResultException
     */
    private function provideNotification(int $orderId): Notification
    {
        return $this->notificationManager->getAuthorisationNotificationForOrderId($orderId);
    }
}
