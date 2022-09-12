<?php

declare(strict_types=1);

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\PaymentStatusUpdate;
use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Models\Order\Status;

/**
 * Class ManualReviewReject.
 */
class ManualReviewReject implements NotificationProcessorInterface
{
    public const EVENT_CODE = 'MANUAL_REVIEW_REJECT';

    /** @var LoggerInterface */
    private $logger;

    /** @var ContainerAwareEventManager */
    private $eventManager;

    /** @var PaymentStatusUpdate */
    private $paymentStatusUpdate;

    /** @var Configuration */
    private $configuration;

    /**
     * Cancellation constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        ContainerAwareEventManager $eventManager,
        PaymentStatusUpdate $paymentStatusUpdate,
        Configuration $configuration
    ) {
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->paymentStatusUpdate = $paymentStatusUpdate->setLogger($this->logger);
        $this->configuration = $configuration;
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
            Event::NOTIFICATION_PROCESS_CANCELLATION,
            [
                'order' => $order,
                'notification' => $notification,
            ]
        );

        if ($notification->isSuccess()) {
            if ('Cancel' === $this->configuration->getManualReviewRejectAction()) {
                $this->paymentStatusUpdate->updatePaymentStatus(
                    $order,
                    Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED
                );
            }
        }
    }
}
