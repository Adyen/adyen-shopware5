<?php

namespace AdyenPayment\Components;

use AdyenPayment\Models\Event;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Voucher\Code;
use Shopware\Models\Voucher\Voucher;

/**
 * Class BasketService
 * @package AdyenPayment\Components
 */
class BasketService
{
    const MODE_PRODUCT = 0;
    const MODE_PREMIUM_PRODUCT = 1;
    const MODE_VOUCHER = 2;
    const MODE_REBATE = 3;
    const MODE_SURCHARGE_DISCOUNT = 4;

    /**
     * @var \sBasket
     */
    private $sBasket;

    /**
     * @var ContainerAwareEventManager
     */
    private $events;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $statusRepository;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository|\Shopware\Models\Order\Repository
     */
    private $orderRepository;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository|\Shopware\Models\Voucher\Repository
     */
    private $voucherRepository;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $voucherCodeRepository;

    /**
     * BasketService constructor.
     * @param ContainerAwareEventManager $events
     * @param ModelManager $modelManager
     */
    public function __construct(
        ContainerAwareEventManager $events,
        ModelManager $modelManager
    ) {
        $this->sBasket = Shopware()->Modules()->Basket();
        $this->events = $events;
        $this->modelManager = $modelManager;

        $this->statusRepository = $modelManager->getRepository(Status::class);
        $this->orderRepository = $modelManager->getRepository(Order::class);
        $this->voucherRepository = $modelManager->getRepository(Voucher::class);
        $this->voucherCodeRepository = $modelManager->getRepository(Code::class);
    }

    /**
     * @param int $orderNumber
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function cancelAndRestoreByOrderNumber(int $orderNumber)
    {
        $order = $this->getOrderByOrderNumber($orderNumber);
        if (!$order) {
            return;
        }

        $this->restoreFromOrder($order);
        $this->cancelOrder($order);
    }

    /**
     * @param int $orderNumber
     * @return Order|null
     */
    public function getOrderByOrderNumber(int $orderNumber)
    {
        return $this->orderRepository->findOneBy(['number' => $orderNumber]);
    }

    /**
     * @param Order $order
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function restoreFromOrder(Order $order)
    {
        $this->sBasket->sDeleteBasket();
        $orderDetails = $order->getDetails();
        foreach ($orderDetails as $orderDetail) {
            $this->processOrderDetail($order, $orderDetail);
        }
        $this->sBasket->sRefreshBasket();
    }

    /**
     * @param Order $order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function cancelOrder(Order $order)
    {
        /** @var Status $statusCanceled */
        $statusCanceled = $this->statusRepository->find(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED);

        $order->setPaymentStatus($statusCanceled);

        $this->modelManager->persist($order);
        $this->modelManager->flush($order);
    }

    /**
     * @param Order $order
     * @param Detail $orderDetail
     * @throws \Enlight_Event_Exception
     */
    private function processOrderDetail(Order $order, Detail $orderDetail)
    {
        $orderDetailFiltered = $this->events->filter(Event::BASKET_BEFORE_PROCESS_ORDER_DETAIL, $orderDetail, [
            'order' => $order,
            'orderDetail' => $orderDetail
        ]);

        if (!$orderDetailFiltered || !$orderDetailFiltered instanceof Detail) {
            $this->events->notify(Event::BASKET_STOPPED_PROCESS_ORDER_DETAIL, [
                'order' => $order,
                'orderDetail' => $orderDetailFiltered,
                'originalOrderDetail' => $orderDetail
            ]);

            return;
        }

        switch ($orderDetailFiltered->getMode()) {
            case self::MODE_PRODUCT:
                $this->addArticle($orderDetailFiltered);
                break;
            case self::MODE_PREMIUM_PRODUCT:
                $this->addPremium($orderDetailFiltered);
                break;
            case self::MODE_VOUCHER:
                $this->addVoucher($orderDetailFiltered);
                break;
            case self::MODE_REBATE:
            case self::MODE_SURCHARGE_DISCOUNT:
                break;
        }

        $this->events->notify(Event::BASKET_AFTER_PROCESS_ORDER_DETAIL, [
            'order' => $order,
            'orderDetail' => $orderDetailFiltered,
            'originalOrderDetail' => $orderDetail
        ]);
    }

    /**
     * @param Detail $orderDetail
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    private function addArticle(Detail $orderDetail)
    {
        $this->sBasket->sAddArticle(
            $orderDetail->getArticleNumber(),
            $orderDetail->getQuantity()
        );
    }

    /**
     * @param Detail $orderDetail
     * @throws \Zend_Db_Adapter_Exception
     */
    private function addPremium(Detail $orderDetail)
    {
        Shopware()->Front()->Request()->setQuery('sAddPremium', $orderDetail->getArticleNumber());
        $this->sBasket->sInsertPremium();
    }

    /**
     * @param Detail $orderDetail
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    private function addVoucher(Detail $orderDetail)
    {
        if (!$orderDetail || !$orderDetail instanceof Detail) {
            return;
        }
        /** @var Voucher $voucher */
        $voucher = $this->voucherRepository->findOneBy(['orderCode' => $orderDetail->getArticleNumber()]);

        if (!$voucher) {
            return;
        }

        $voucherCode = $voucher->getVoucherCode();

        if ($voucher->getModus() === 1) {
            /** @var Code $voucherCodeObject */
            $voucherCodeObject = $this->voucherCodeRepository->findOneBy([
                'voucherId' => $voucher->getId(),
                'id' => $orderDetail->getArticleId()
            ]);
            if ($voucherCodeObject) {
                $voucherCode = $voucherCodeObject->getCode();
                $voucherCodeObject->setCustomerId(null);
                $voucherCodeObject->setCashed(0);
                $this->modelManager->persist($voucherCodeObject);
            }
        }
        $this->modelManager->remove($orderDetail);
        $this->modelManager->flush();

        $this->sBasket->sAddVoucher(
            $voucherCode
        );
    }
}
