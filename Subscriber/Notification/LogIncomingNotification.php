<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Notification;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use AdyenPayment\Models\Event;
use Psr\Log\LoggerInterface;

/**
 * Class SaveNotification
 */
class LogIncomingNotification implements SubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LogIncomingNotification constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Event::NOTIFICATION_RECEIVE => 'logNotifications'
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function logNotifications(Enlight_Event_EventArgs $args)
    {
        $items = $args->get('items');

        foreach ($items as $item) {
            $this->logger->debug('Incoming notification', ['json' => $item]);
        }
    }
}
