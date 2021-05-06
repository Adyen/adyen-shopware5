<?php

namespace AdyenPayment\Components;

use AdyenPayment\Basket\Restore\DetailAttributesRestorer;
use AdyenPayment\Models\Event;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Enlight_Components_Session_Namespace;
use Enlight_Event_Exception;
use Enlight_Exception;
use sBasket;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Voucher\Code;
use Shopware\Models\Voucher\Repository;
use Shopware\Models\Voucher\Voucher;
use Zend_Db_Adapter_Exception;
use Zend_Db_Select_Exception;
use Zend_Db_Statement_Exception;

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
     * @var sBasket
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
     * @var ObjectRepository|EntityRepository
     */
    private $statusRepository;

    /**
     * @var ObjectRepository|EntityRepository|\Shopware\Models\Order\Repository
     */
    private $orderRepository;

    /**
     * @var ObjectRepository|EntityRepository|Repository
     */
    private $voucherRepository;

    /**
     * @var ObjectRepository|EntityRepository
     */
    private $voucherCodeRepository;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @var DetailAttributesRestorer
     */
    private $detailAttributesRestorer;

    /**
     * BasketService constructor.
     * @param ContainerAwareEventManager $events
     * @param ModelManager $modelManager
     * @param Enlight_Components_Db_Adapter_Pdo_Mysql $db
     * @param Enlight_Components_Session_Namespace $session
     * @param DetailAttributesRestorer $detailAttributesRestorer
     */
    public function __construct(
        ContainerAwareEventManager $events,
        ModelManager $modelManager,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        Enlight_Components_Session_Namespace $session,
        DetailAttributesRestorer $detailAttributesRestorer
    ) {
        $this->sBasket = Shopware()->Modules()->Basket();
        $this->events = $events;
        $this->modelManager = $modelManager;
        $this->db = $db;
        $this->session = $session;

        $this->statusRepository = $modelManager->getRepository(Status::class);
        $this->orderRepository = $modelManager->getRepository(Order::class);
        $this->voucherRepository = $modelManager->getRepository(Voucher::class);
        $this->voucherCodeRepository = $modelManager->getRepository(Code::class);
        $this->detailAttributesRestorer = $detailAttributesRestorer;
    }

    /**
     * @param string $orderNumber
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function cancelAndRestoreByOrderNumber(string $orderNumber)
    {
        $order = $this->getOrderByOrderNumber($orderNumber);
        if (!$order) {
            return;
        }

        $this->restoreFromOrder($order);
        $this->cancelOrder($order);
    }

    /**
     * @param string $orderNumber
     * @return Order|null|object
     */
    public function getOrderByOrderNumber(string $orderNumber): Order
    {
        return $this->orderRepository->findOneBy(['number' => $orderNumber]);
    }

    /**
     * @param Order $order
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function restoreFromOrder(Order $order)
    {
        $this->sBasket->sDeleteBasket();
        $orderDetails = $order->getDetails();
        foreach ($orderDetails as $orderDetail) {
            $this->processOrderDetail($order, $orderDetail);
        }

        $this->events->notify(Event::BASKET_RESTORE_FROM_ORDER, [
            'order' => $order
        ]);

        $this->sBasket->sRefreshBasket();
    }

    /**
     * @param Order $order
     * @throws ORMException
     * @throws OptimisticLockException
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
     * @throws Enlight_Event_Exception
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
            case self::MODE_SURCHARGE_DISCOUNT:
                $this->addArticle($orderDetailFiltered);
                break;
            case self::MODE_PREMIUM_PRODUCT:
                $this->addPremium($orderDetailFiltered);
                break;
            case self::MODE_VOUCHER:
                $this->addVoucher($orderDetailFiltered);
                break;
            case self::MODE_REBATE:
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
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Select_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function addArticle(Detail $orderDetail)
    {
        if (empty($orderDetail->getArticleNumber()) || !$this->isArticlesDetails($orderDetail->getArticleNumber())) {
            // The order item doesn't have an article number or it isn't a regular Shopware article
            // add it to the basket manually
            $basketDetailId = $this->insertInToBasket($orderDetail);
        } else {
            $basketDetailId = $this->sBasket->sAddArticle(
                $orderDetail->getArticleNumber(),
                $orderDetail->getQuantity()
            );
        }

        $this->detailAttributesRestorer->restore($orderDetail->getId(), $basketDetailId);
    }

    /**
     * Searches in the s_articles_details table with the ordernumber column and returns true if an article is found
     *
     * @param string $articleDetailNumber
     * @return bool
     * @throws Zend_Db_Select_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function isArticlesDetails(string $articleDetailNumber): bool
    {
        $result = $this->db->select()
            ->from('s_articles_details')
            ->where('ordernumber=?', $articleDetailNumber)
            ->query()
            ->fetch();

        return !empty($result);
    }

    /**
     * Inserts data from a order detail row into a basket detail and returns the inserted ID
     *
     * @param Detail $optionData
     * @return int
     * @throws Zend_Db_Adapter_Exception
     */
    private function insertInToBasket(Detail $optionData): int
    {
        $this->db->insert('s_order_basket', [
            'sessionID' => $this->session->get('sessionId'),
            'userID' => $this->session->get('sUserId') || 0,
            'articlename' => $optionData->getArticleName(),
            'ordernumber' => $optionData->getArticleNumber(),
            'articleID' => $optionData->getArticleId(),
            'quantity' => $optionData->getQuantity(),
            'price' => $optionData->getPrice(),
            'netprice' => $optionData->getPrice() === null
                ? 0
                : $optionData->getPrice() / (1 + ($optionData->getTaxRate() / 100)),
            'tax_rate' => $optionData->getTaxRate(),
            'modus' => $optionData->getMode(),
            'esdarticle' => $optionData->getEsdArticle(),
            'config' => $optionData->getConfig(),
            'datum' => (new DateTime())->format('Y-m-d H:i:s'),
            'currencyFactor' => Shopware()->Shop()->getCurrency()->getFactor()
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * @param Detail $orderDetail
     * @throws Zend_Db_Adapter_Exception
     */
    private function addPremium(Detail $orderDetail)
    {
        Shopware()->Front()->Request()->setQuery('sAddPremium', $orderDetail->getArticleNumber());
        $this->sBasket->sInsertPremium();
    }

    /**
     * @param Detail $orderDetail
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
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
