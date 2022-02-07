<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Models\PaymentInfo;
use Doctrine\Persistence\ObjectRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use Shopware\Components\Model\ModelManager;

final class AddStoredMethodIdOnOrderSubscriber implements SubscriberInterface
{
    private ModelManager $modelManager;
    private ObjectRepository $paymentInfoRepository;
    private Enlight_Components_Session_Namespace $session;

    public function __construct(ModelManager $modelManager, Enlight_Components_Session_Namespace $session)
    {
        $this->modelManager = $modelManager;
        $this->paymentInfoRepository = $this->modelManager->getRepository(PaymentInfo::class);
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return ['Shopware_Modules_Order_SaveOrder_ProcessDetails' => 'persistPaymentInfoStoredMethodId'];
    }

    public function persistPaymentInfoStoredMethodId(Enlight_Event_EventArgs $args)
    {
        $paymentInfoId = $this->session->get(AdyenPayment::SESSION_ADYEN_PAYMENT_INFO_ID);
        $storedMethodId = (string) $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID, '');

        if (null === $paymentInfoId) {
            return $args->getReturn();
        }

        /** @var PaymentInfo $paymentInfo */
        $paymentInfo = $this->paymentInfoRepository->findOneBy([
            'id' => $paymentInfoId,
        ]);

        if ($paymentInfo) {
            $paymentInfo->setStoredMethodId($storedMethodId);
            $this->modelManager->persist($paymentInfo);
            $this->modelManager->flush($paymentInfo);
        }

        return $args->getReturn();
    }
}
