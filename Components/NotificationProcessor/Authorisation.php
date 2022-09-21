<?php

declare(strict_types=1);

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Components\PaymentStatusUpdate;
use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use AdyenPayment\Models\PaymentInfo;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Status;

/**
 * Class Authorisation.
 */
class Authorisation implements NotificationProcessorInterface
{
    public const EVENT_CODE = 'AUTHORISATION';

    /** @var LoggerInterface */
    private $logger;

    /** @var ContainerAwareEventManager */
    private $eventManager;

    /** @var PaymentStatusUpdate */
    private $paymentStatusUpdate;

    /** @var ModelManager */
    private $modelManager;

    /** @var \Doctrine\ORM\EntityRepository */
    private $paymentInfoRepository;

    /**
     * Authorisation constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        ContainerAwareEventManager $eventManager,
        PaymentStatusUpdate $paymentStatusUpdate,
        ModelManager $modelManager
    ) {
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->paymentStatusUpdate = $paymentStatusUpdate->setLogger($this->logger);
        $this->modelManager = $modelManager;
        $this->paymentInfoRepository = $modelManager->getRepository(PaymentInfo::class);
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
        if (!$order) {
            return;
        }

        $this->eventManager->notify(
            Event::NOTIFICATION_PROCESS_AUTHORISATION,
            [
                'order' => $order,
                'notification' => $notification,
            ]
        );

        $status = $notification->isSuccess()
            ? Status::PAYMENT_STATE_COMPLETELY_PAID
            : Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;

        $this->paymentStatusUpdate->updatePaymentStatus($order, $status);

        if ($notification->isSuccess()) {
            /** @var PaymentInfo $paymentInfo */
            $paymentInfo = $this->paymentInfoRepository->findOneBy([
                'orderId' => $order->getId(),
            ]);
            if (!$paymentInfo) {
                return;
            }

            $paymentInfo->setPspReference($notification->getPspReference());
            $this->modelManager->persist($paymentInfo);
            $this->modelManager->flush($paymentInfo);
        }
    }
}
