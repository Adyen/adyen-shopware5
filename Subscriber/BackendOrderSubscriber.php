<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use AdyenPayment\Components\NotificationManager;
use AdyenPayment\Models\PaymentInfo;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Backend_Order;

/**
 * Class CheckoutSubscriber
 * @package AdyenPayment\Subscriber
 */
class BackendOrderSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ObjectRepository|EntityRepository
     */
    private $paymentInfoRepository;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * BackendOrderSubscriber constructor.
     * @param ModelManager $modelManager
     * @param NotificationManager $notificationManager
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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onBackendOrder'
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendOrder(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Order $subject */
        $subject = $args->getSubject();

        if ($subject->Request()->getActionName() !== 'getList') {
            return;
        }

        $data = $subject->View()->getAssign('data');

        $this->addTransactionData($data);

        $subject->View()->assign('data', $data);
    }

    /**
     * @param array $data
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function addTransactionData(array &$data)
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
                ]);
            }
        }
    }
}
