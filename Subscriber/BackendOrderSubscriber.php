<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Components\NotificationManager;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\PaymentInfo;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Backend_Order;

/**
 * Class CheckoutSubscriber.
 */
class BackendOrderSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $paymentInfoRepository;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * BackendOrderSubscriber constructor.
     */
    public function __construct(
        ModelManager $modelManager,
        NotificationManager $notificationManager
    ) {
        $this->modelManager = $modelManager;
        $this->paymentInfoRepository = $this->modelManager->getRepository(PaymentInfo::class);
        $this->notificationManager = $notificationManager;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{Enlight_Controller_Action_PostDispatchSecure_Backend_Order: 'onBackendOrder'}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onBackendOrder',
        ];
    }

    public function onBackendOrder(Enlight_Event_EventArgs $args): void
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
