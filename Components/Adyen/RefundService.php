<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\Modification;
use AdyenPayment\Components\NotificationManager;
use AdyenPayment\Models\Notification;
use AdyenPayment\Models\PaymentInfo;
use AdyenPayment\Models\Refund;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

class RefundService
{
    /**
     * @var ApiClientMap
     */
    private $apiClientMap;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $paymentInfoRepository;

    public function __construct(
        ApiClientMap $apiClientMap,
        ModelManager $modelManager,
        NotificationManager $notificationManager
    ) {
        $this->apiClientMap = $apiClientMap;
        $this->modelManager = $modelManager;
        $this->notificationManager = $notificationManager;
        $this->paymentInfoRepository = $modelManager->getRepository(PaymentInfo::class);
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

    private function provideNotification(int $orderId): Notification
    {
        /** @var PaymentInfo $paymentInfo */
        $paymentInfo = $this->paymentInfoRepository->findOneBy(['orderId' => $orderId]);
        if ($paymentInfo && '' !== $paymentInfo->getPspReference()) {
            return $this->notificationManager->getLastNotificationForPspReference($paymentInfo->getPspReference());
        }

        return $this->notificationManager->getLastNotificationForOrderId($orderId);
    }
}
