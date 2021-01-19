<?php

namespace AdyenPayment\Components;

use Adyen\Service\NotificationReceiver as AdyenNotificationReceiver;
use AdyenPayment\Components\Builder\NotificationBuilder;
use AdyenPayment\Exceptions\InvalidParameterException;
use AdyenPayment\Exceptions\OrderNotFoundException;
use AdyenPayment\Models\Feedback\NotificationItemFeedback;
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
     * @var AdyenNotificationReceiver
     */
    private $notificationReceiver;

    /**
     * IncomingNotificationManager constructor.
     * @param LoggerInterface $logger
     * @param NotificationBuilder $notificationBuilder
     * @param ModelManager $entityManager
     */
    public function __construct(
        LoggerInterface $logger,
        NotificationBuilder $notificationBuilder,
        ModelManager $entityManager,
        AdyenNotificationReceiver $notificationReceiver
    ) {
        $this->logger = $logger;
        $this->notificationBuilder = $notificationBuilder;
        $this->entityManager = $entityManager;
        $this->notificationReceiver = $notificationReceiver;
    }

    /**
     * @param array $notificationItems
     * @return \Generator
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(array $notificationItems)
    {
        foreach ($notificationItems as $notificationItem) {
            $notificationRequest = $notificationItem['NotificationRequestItem'] ?? [];
            if (empty($notificationRequest)) {
                continue;
            }

            if ($this->skipNotification($notificationRequest)) {
                $this->logger->info('Skipped notification', ['eventCode' => $notificationRequest['eventCode'] ?? '']);
                continue;
            }

            try {
                $notification = $this->notificationBuilder->fromParams($notificationRequest);
                $this->entityManager->persist($notification);
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

    private function skipNotification(array $notificationRequest): bool
    {
        if ($this->notificationReceiver->isReportNotification($notificationRequest['eventCode'])) {
            return true;
        }

        return false;
    }
}
