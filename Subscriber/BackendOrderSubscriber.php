<?php declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\NotificationManager;
use MeteorAdyen\MeteorAdyen;
use MeteorAdyen\Models\PaymentInfo;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Backend_Order;

/**
 * Class CheckoutSubscriber
 * @package MeteorAdyen\Subscriber
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
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onBackendOrder',
            'Shopware_Modules_Order_SendMail_Send' => 'onSendMail'
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

            if ($order['payment']['name'] !== MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD) {
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
