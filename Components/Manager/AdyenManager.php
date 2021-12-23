<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use AdyenPayment\Models\PaymentInfo;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Shopware\Models\Order\Order;

/**
 * Class AdyenManager.
 */
class AdyenManager
{
    /**
     * @var EntityManagerInterface
     */
    private $modelManager;

    public function __construct(EntityManagerInterface $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function storePaymentData(PaymentInfo $transaction, string $paymentData): void
    {
        $transaction->setPaymentData($paymentData);
        $this->modelManager->persist($transaction);
        $this->modelManager->flush();
    }

    public function fetchOrderPaymentData(?Order $order): string
    {
        if (!$order) {
            return '';
        }

        /** @var PaymentInfo $transaction */
        $transaction = $this->getPaymentInfoRepository()->findOneBy(['orderId' => $order->getId()]);

        return $transaction ? $transaction->getPaymentData() : '';
    }

    private function getPaymentInfoRepository(): ObjectRepository
    {
        return $this->modelManager->getRepository(PaymentInfo::class);
    }
}
