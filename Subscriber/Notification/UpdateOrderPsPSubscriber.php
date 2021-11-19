<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Notification;

use AdyenPayment\Components\Manager\OrderManagerInterface;
use AdyenPayment\Models\Event;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

class UpdateOrderPsPSubscriber implements SubscriberInterface
{
    /**
     * @var OrderManagerInterface
     */
    private $orderManager;

    public function __construct(OrderManagerInterface $orderManager)
    {
        $this->orderManager = $orderManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Event::NOTIFICATION_PROCESS_AUTHORISATION => '__invoke',
        ];
    }

    public function __invoke(Enlight_Event_EventArgs $args): void
    {
        /**
         * @var \Shopware\Models\Order\Order      $order
         * @var \AdyenPayment\Models\Notification $notification
         */
        $order = $args->get('order');
        $notification = $args->get('notification');
        if (!$notification->isSuccess()) {
            return;
        }

        if ($order->getTransactionId() === $notification->getPspReference()) {
            return;
        }

        $this->orderManager->updatePspReference($order, $notification->getPspReference());
    }
}
