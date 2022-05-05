<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use AdyenPayment\Components\Builder\NotificationBuilder;
use AdyenPayment\Exceptions\DuplicateNotificationException;
use AdyenPayment\Exceptions\InvalidParameterException;
use AdyenPayment\Exceptions\OrderNotFoundException;
use AdyenPayment\Models\Feedback\TextNotificationItemFeedback;
use AdyenPayment\Models\TextNotification;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;

/**
 * Class IncomingNotificationManager.
 */
class IncomingNotificationManager
{
    private LoggerInterface $logger;
    private NotificationBuilder $notificationBuilder;
    private ModelManager $entityManager;
    private NotificationManager $notificationManager;

    /**
     * IncomingNotificationManager constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        NotificationBuilder $notificationBuilder,
        ModelManager $entityManager,
        NotificationManager $notificationManager
    ) {
        $this->logger = $logger;
        $this->notificationBuilder = $notificationBuilder;
        $this->entityManager = $entityManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param TextNotification[] $textNotifications
     *
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function convertNotifications(array $textNotifications): void
    {
        foreach ($textNotifications as $textNotificationItem) {
            try {
                if (!empty($textNotificationItem->getTextNotification())) {
                    $notification = $this->notificationBuilder->fromParams(
                        json_decode($textNotificationItem->getTextNotification(), true)
                    );

                    $this->notificationManager->guardDuplicate($notification);

                    $this->entityManager->persist($notification);
                }
            } catch (InvalidParameterException $exception) {
                $this->logger->warning(
                    $exception->getMessage().' '.$textNotificationItem->getTextNotification()
                );
            } catch (OrderNotFoundException $exception) {
                $this->logger->warning(
                    $exception->getMessage().' '.$textNotificationItem->getTextNotification()
                );
            } catch (DuplicateNotificationException $exception) {
                $this->logger->notice(
                    $exception->getMessage()
                );
            }
            $this->entityManager->remove($textNotificationItem);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveTextNotification(array $textNotificationItems): \Generator
    {
        foreach ($textNotificationItems as $textNotificationItem) {
            try {
                if (!empty($textNotificationItem['NotificationRequestItem'])) {
                    if ($this->skipNotification($textNotificationItem['NotificationRequestItem'])) {
                        $this->logger->info(
                            'Skipped notification',
                            ['eventCode' => $textNotificationItem['eventCode'] ?? '']
                        );

                        continue;
                    }

                    $textNotification = new TextNotification();
                    $textNotification->setTextNotification(
                        json_encode($textNotificationItem['NotificationRequestItem'])
                    );
                    $this->entityManager->persist($textNotification);
                }
            } catch (ORMException $exception) {
                $this->logger->warning($exception->getMessage());
                yield new TextNotificationItemFeedback($exception->getMessage(), $textNotificationItem);
            }
        }
        $this->entityManager->flush();
    }

    private function skipNotification(array $notificationRequest): bool
    {
        if (!empty($notificationRequest['eventCode']) &&
            false !== mb_strpos($notificationRequest['eventCode'], 'REPORT_')) {
            return true;
        }

        return false;
    }
}
