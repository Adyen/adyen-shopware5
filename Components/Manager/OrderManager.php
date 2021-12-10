<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use Doctrine\ORM\EntityManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

final class OrderManager implements OrderManagerInterface
{
    private EntityManager $modelManager;

    public function __construct(EntityManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function save(Order $order): void
    {
        $this->modelManager->persist($order);
        $this->modelManager->flush($order);
    }

    public function updatePspReference(Order $order, string $pspReference): void
    {
        $order = $order->setTransactionId($pspReference);
        $this->modelManager->persist($order);
    }

    public function updatePayment(Order $order, string $pspReference, Status $paymentStatus): void
    {
        $order->setPaymentStatus($paymentStatus);
        $order = $order->setTransactionId($pspReference);
        $this->modelManager->persist($order);
    }
}
