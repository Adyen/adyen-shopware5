<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\Components\NotificationManager;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware_Controllers_Backend_Order;

final class BackendOrderSubscriber implements SubscriberInterface
{
    private EntityRepository $paymentInfoRepository;
    private NotificationManager $notificationManager;

    public function __construct(EntityRepository $paymentInfoRepository, NotificationManager $notificationManager)
    {
        $this->paymentInfoRepository = $paymentInfoRepository;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{Enlight_Controller_Action_PostDispatchSecure_Backend_Order: 'onBackendOrder'}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => '__invoke',
        ];
    }

    public function __invoke(Enlight_Event_EventArgs $args): void
    {
        /** @var Shopware_Controllers_Backend_Order $subject */
        $subject = $args->getSubject();

        if ('getList' !== $subject->Request()->getActionName()) {
            return;
        }

        $data = $subject->View()->getAssign('data');

        $this->addTransactionData($data);

        $subject->View()->assign('data', $data);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function addTransactionData(array &$data): void
    {
        foreach ($data as &$order) {
            $order['adyenTransaction'] = null;
            $order['adyenNotification'] = null;
            $order['adyenRefundable'] = false;

            $source = (int) ($order['payment']['source'] ?? null);
            if (!SourceType::load($source)->equals(SourceType::adyen())) {
                continue;
            }

            $lastNotification = $this->notificationManager->getLastNotificationForOrderId($order['id']);
            if ($lastNotification) {
                $transaction = $this->paymentInfoRepository->findOneBy(['orderId' => $order['id']]);
                if ($transaction) {
                    $order['adyenTransaction'] = $transaction;
                }

                $order['adyenNotification'] = $lastNotification;
                $order['adyenRefundable'] = in_array($lastNotification->getEventCode(), [
                    'AUTHORISATION',
                    'CAPTURE',
                ], true);
            }
        }
    }
}
