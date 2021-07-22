<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use AdyenPayment\Models\PaymentInfo;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Enlight_Components_Session_Namespace;
use AdyenPayment\AdyenPayment;
use Shopware\Models\Order\Order;

/**
 * Class AdyenManager
 * @package AdyenPayment\Components\Manager
 */
class AdyenManager
{
    /**
     * @var EntityManagerInterface
     */
    private $modelManager;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(
        EntityManagerInterface $modelManager,
        Enlight_Components_Session_Namespace $session
    ) {
        $this->modelManager = $modelManager;
        $this->session = $session;
    }

    public function storePaymentData(PaymentInfo $transaction, string $paymentData)
    {
        $transaction->setPaymentData($paymentData);
        $this->modelManager->persist($transaction);
        $this->modelManager->flush();
    }

    /**
     * @param Order|null $order
     * @return string
     */
    public function fetchOrderPaymentData($order): string
    {
        if (!$order) {
            return '';
        }

        /* @var PaymentInfo $transaction */
        $transaction = $this->getPaymentInfoRepository()->findOneBy(['orderId' => $order->getId()]);

        return $transaction ? $transaction->getPaymentData() : '';
    }

    public function unsetValidPaymentSession()
    {
        $this->session->offsetUnset(AdyenPayment::SESSION_ADYEN_PAYMENT_VALID);
    }

    private function getPaymentInfoRepository(): ObjectRepository
    {
        return $this->modelManager->getRepository(PaymentInfo::class);
    }
}
