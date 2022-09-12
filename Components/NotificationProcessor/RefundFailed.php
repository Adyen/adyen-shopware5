<?php

declare(strict_types=1);

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Components\PaymentStatusUpdate;
use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;

/**
 * Class RefundFailed.
 */
class RefundFailed implements NotificationProcessorInterface
{
    public const EVENT_CODE = 'REFUND_FAILED';

    /** @var LoggerInterface */
    private $logger;

    /** @var ContainerAwareEventManager */
    private $eventManager;

    /** @var PaymentStatusUpdate */
    private $paymentStatusUpdate;

    /**
     * Authorisation constructor.
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
     * Returns boolean on whether this processor can process the Notification object.
     */
    public function supports(Notification $notification): bool
    {
        return self::EVENT_CODE === mb_strtoupper($notification->getEventCode());
    }

    /**
     * Actual processing of the notification.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Enlight_Event_Exception
     */
    public function process(Notification $notification): void
    {
        $order = $notification->getOrder();

        $this->eventManager->notify(
            Event::NOTIFICATION_PROCESS_REFUND_FAILED,
            [
                'order' => $order,
                'notification' => $notification,
            ]
        );
    }
}
