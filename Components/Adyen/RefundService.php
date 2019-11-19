<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\Modification;
use MeteorAdyen\Components\NotificationManager;
use MeteorAdyen\Models\Refund;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

class RefundService
{
    /**
     * @var ApiFactory
     */
    private $apiFactory;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * PaymentMethodService constructor.
     * @param ApiFactory $apiFactory
     * @param ModelManager $modelManager
     * @param NotificationManager $notificationManager
     */
    public function __construct(
        ApiFactory $apiFactory,
        ModelManager $modelManager,
        NotificationManager $notificationManager
    ) {
        $this->apiFactory = $apiFactory;
        $this->modelManager = $modelManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param int $orderId
     * @return Refund
     * @throws AdyenException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doRefund(int $orderId): Refund
    {
        $order = $this->modelManager->find(Order::class, $orderId);
        $apiClient = $this->apiFactory->create($order->getShop());
        $modification = new Modification($apiClient);

        $notification = $this->notificationManager->getLastNotificationForOrderId($orderId);

        $request = [
            'originalReference' => $notification->getPspReference(),
            'modificationAmount' => [
                'value' => $notification->getAmountValue(),
                'currency' => $notification->getAmountCurrency(),
            ],
            'merchantAccount' => $notification->getMerchantAccountCode()
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
}
