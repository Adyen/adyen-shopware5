<?php

namespace MeteorAdyen\Components\NotificationProcessor;

use MeteorAdyen\Models\Event;
use MeteorAdyen\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class Authorisation implements NotificationProcessorInterface
{
    const EVENT_CODE = 'authorisation';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;

    /**
     * Authorisation constructor.
     * @param LoggerInterface $logger
     * @param ModelManager $modelManager
     * @param ContainerAwareEventManager $eventManager
     */
    public function __construct(
        LoggerInterface $logger,
        ModelManager $modelManager,
        ContainerAwareEventManager $eventManager
    ) {
        $this->logger = $logger;
        $this->modelManager = $modelManager;
        $this->eventManager = $eventManager;
    }

    /**
     * Returns boolean on whether this processor can process the Notification object
     *
     * @param Notification $notification
     * @return boolean
     */
    public function supports(Notification $notification): bool
    {
        return strtolower($notification->getEventCode()) == self::EVENT_CODE;
    }

    /**
     * Actual processing of the notification
     *
     * @param Notification $notification
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Enlight_Event_Exception
     */
    public function process(Notification $notification)
    {
        $order = $notification->getOrderId();
        if (!$order) {
            $this->logger->error('No order found', [
                'eventCode' => $notification->getEventCode(),
                'status ' => $notification->getStatus()
            ]);
            return;
        }

        $this->eventManager->notify(Event::NOTIFICATION_PROCESS_AUTHORISATION,
            [
                'order' => $order,
                'notification' => $notification
            ]
        );

        if ($notification->isSuccess()) {
            $this->success($notification, $order);
            return;
        }

        $this->fail($notification, $order);
    }

    /**
     * @param Notification $notification
     * @param Order $order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Enlight_Event_Exception
     */
    private function success(
        Notification $notification,
        Order $order
    ) {
        $this->logger->debug(
            'Order paid',
            [
                'orderId' => $order->getId(),
                'merchantReference' => $notification->getOrderId()->getNumber(),
                'pspReference' => $notification->getPspReference()
            ]
        );

        $orderStatus = $this->modelManager->find(Status::class, Status::ORDER_STATE_COMPLETED);
        $paymentStatus = $this->modelManager->find(Status::class, Status::PAYMENT_STATE_THE_CREDIT_HAS_BEEN_ACCEPTED);

        $order->setOrderStatus($orderStatus);
        $order->setPaymentStatus($paymentStatus);

        $this->modelManager->persist($order);
        $this->modelManager->flush();
    }

    /**
     * @param Notification $notification
     * @param Order $order
     */
    private function fail(Notification $notification, Order $order)
    {
        // TODO
    }
}
