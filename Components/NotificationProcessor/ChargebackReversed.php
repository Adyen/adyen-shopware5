<?php

namespace MeteorAdyen\Components\NotificationProcessor;

use MeteorAdyen\Components\PaymentStatusUpdate;
use MeteorAdyen\Models\Event;
use MeteorAdyen\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Models\Order\Status;

/**
 * Class ChargebackReversed
 * @package MeteorAdyen\Components\NotificationProcessor
 */
class ChargebackReversed implements NotificationProcessorInterface
{
    const EVENT_CODE = 'CHARGEBACK_REVERSED';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;
    /**
     * @var PaymentStatusUpdate
     */
    private $paymentStatusUpdate;

    /**
     * Authorisation constructor.
     * @param LoggerInterface $logger
     * @param ContainerAwareEventManager $eventManager
     * @param PaymentStatusUpdate $paymentStatusUpdate
     */
    public function __construct(
        LoggerInterface $logger,
        ContainerAwareEventManager $eventManager,
        PaymentStatusUpdate $paymentStatusUpdate
    ) {
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->paymentStatusUpdate = $paymentStatusUpdate->setLogger($this->logger);
    }

    /**
     * Returns boolean on whether this processor can process the Notification object
     *
     * @param Notification $notification
     * @return boolean
     */
    public function supports(Notification $notification): bool
    {
        return strtoupper($notification->getEventCode()) === self::EVENT_CODE;
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
        $order = $notification->getOrder();
        if (!$order) {
            $this->logger->error('No order found', [
                'eventCode' => $notification->getEventCode(),
                'status ' => $notification->getStatus()
            ]);
            return;
        }

        $this->eventManager->notify(
            Event::NOTIFICATION_PROCESS_CHARGEBACK_REVERSED,
            [
                'order' => $order,
                'notification' => $notification
            ]
        );

        $this->paymentStatusUpdate->updatePaymentStatus($order, Status::PAYMENT_STATE_THE_CREDIT_HAS_BEEN_ACCEPTED);
    }
}
