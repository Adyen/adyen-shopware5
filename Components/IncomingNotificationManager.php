<?php

namespace AdyenPayment\Components;

use AdyenPayment\Components\Builder\NotificationBuilder;
use AdyenPayment\Exceptions\InvalidParameterException;
use AdyenPayment\Exceptions\OrderNotFoundException;
use AdyenPayment\Models\Feedback\NotificationItemFeedback;
use AdyenPayment\Models\Feedback\TextNotificationItemFeedback;
use AdyenPayment\Models\TextNotification;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;

/**
 * Class IncomingNotificationManager
 * @package AdyenPayment\Components
 */
class IncomingNotificationManager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NotificationBuilder
     */
    private $notificationBuilder;

    /**
     * @var ModelManager
     */
    private $entityManager;

    /**
     * IncomingNotificationManager constructor.
     * @param LoggerInterface $logger
     * @param NotificationBuilder $notificationBuilder
     * @param ModelManager $entityManager
     */
    public function __construct(
        LoggerInterface $logger,
        NotificationBuilder $notificationBuilder,
        ModelManager $entityManager
    ) {
        $this->logger = $logger;
        $this->notificationBuilder = $notificationBuilder;
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $notificationItems
     * @return \Generator
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveNotification(array $notificationItems)
    {
        foreach ($notificationItems as $notificationItem) {
            try {
                if (!empty($notificationItem['NotificationRequestItem'])) {
                    $notification = $this->notificationBuilder->fromParams(
                        $notificationItem['NotificationRequestItem']
                    );
                    $this->entityManager->persist($notification);
                }
            } catch (InvalidParameterException $exception) {
                $this->logger->warning($exception->getMessage());
                yield new NotificationItemFeedback($exception->getMessage(), $notificationItem);
            } catch (OrderNotFoundException $exception) {
                $this->logger->warning($exception->getMessage());
                yield new NotificationItemFeedback($exception->getMessage(), $notificationItem);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @param array $textNotificationItems
     * @return \Generator
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveTextNotification(array $textNotificationItems): \Generator
    {
        foreach ($textNotificationItems as $textNotificationItem) {
            try {
                if (!empty($textNotificationItem['NotificationRequestItem'])) {
                    $textNotification = new TextNotification();
                    $textNotification->setTextNotification(json_encode($textNotificationItem['NotificationRequestItem']));
                    $this->entityManager->persist($textNotification);
                }
            } catch (ORMException $exception) {
                $this->logger->warning($exception->getMessage());
                yield new TextNotificationItemFeedback($exception->getMessage(), $textNotificationItem);
            }
        }
        $this->entityManager->flush();
    }
}
