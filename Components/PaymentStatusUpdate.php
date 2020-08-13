<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class PaymentStatusUpdate
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PaymentStatusUpdate constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
    }

    /**
     * @param Order $order
     * @param int $statusId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function updateOrderStatus(Order $order, int $statusId)
    {
        $orderStatus = $this->modelManager->find(Status::class, $statusId);

        if ($this->logger) {
            $this->logger->debug('Update order status', [
                'number' => $order->getNumber(),
                'oldStatus' => $order->getOrderStatus()->getName(),
                'newStatus' => $orderStatus->getName()
            ]);
        }

        $order->setOrderStatus($orderStatus);
        $this->modelManager->persist($order);
        $this->modelManager->flush();
    }

    /**
     * @param Order $order
     * @param int $statusId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function updatePaymentStatus(Order $order, int $statusId)
    {
        $paymentStatus = $this->modelManager->find(Status::class, $statusId);

        if ($this->logger) {
            $this->logger->debug('Update order payment status', [
                'number' => $order->getNumber(),
                'oldStatus' => $order->getPaymentStatus()->getName(),
                'newStatus' => $paymentStatus->getName()
            ]);
        }

        $order->setPaymentStatus($paymentStatus);
        $this->modelManager->persist($order);
        $this->modelManager->flush();
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
