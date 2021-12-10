<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Notification;

use AdyenPayment\Models\Event;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Psr\Log\LoggerInterface;

final class LogIncomingNotificationSubscriber implements SubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Event::NOTIFICATION_RECEIVE => '__invoke',
        ];
    }

    public function __invoke(Enlight_Event_EventArgs $args): void
    {
        $items = $args->get('items') ?? [];
        foreach ($items as $item) {
            $this->logger->debug('Incoming notification', ['json' => $item]);
        }
    }
}
