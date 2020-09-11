<?php

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
 * Class Authorisation
 * @package AdyenPayment\Components\NotificationProcessor
 */
class Authorisation implements NotificationProcessorInterface
{
    const EVENT_CODE = 'AUTHORISATION';

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
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $paymentInfoRepository;

    /**
     * Authorisation constructor.
     * @param LoggerInterface $logger
     * @param ContainerAwareEventManager $eventManager
     * @param PaymentStatusUpdate $paymentStatusUpdate
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
        $this->eventManager->notify(
            Event::NOTIFICATION_PROCESS_AUTHORISATION,
            [
                'order' => $order,
                'notification' => $notification
            ]
        );

        $status = $notification->isSuccess() ?
            Status::PAYMENT_STATE_COMPLETELY_PAID :
            Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;

        $this->paymentStatusUpdate->updatePaymentStatus($order, $status);

        if ($notification->isSuccess()) {
            /** @var PaymentInfo $paymentInfo */
            $paymentInfo = $this->paymentInfoRepository->findOneBy([
                'orderId' => $order->getId()
            ]);

            $paymentInfo->setPspReference($notification->getPspReference());
            $this->modelManager->persist($paymentInfo);
            $this->modelManager->flush($paymentInfo);
        }
    }
}
