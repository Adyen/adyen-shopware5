<?php

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Models\PaymentInfo;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Enlight_Event_Exception;
use AdyenPayment\Components\PaymentStatusUpdate;
use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Status;

/**
 * Class Capture
 * @package AdyenPayment\Components\NotificationProcessor
 */
class Capture implements NotificationProcessorInterface
{
    const EVENT_CODE = 'CAPTURE';

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
     * @var \Doctrine\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $paymentInfoRepository;


    /**
     * Capture constructor.
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
     * @param Notification $notification
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws Enlight_Event_Exception
     */
    public function process(Notification $notification)
    {
        $order = $notification->getOrder();

        $this->eventManager->notify(
            Event::NOTIFICATION_PROCESS_CAPTURE,
            [
                'order' => $order,
                'notification' => $notification
            ]
        );

        if ($notification->isSuccess()) {
            $this->paymentStatusUpdate->updatePaymentStatus(
                $order,
                Status::PAYMENT_STATE_COMPLETELY_PAID
            );

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
