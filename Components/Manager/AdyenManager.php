<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Enlight_Components_Session_Namespace;
use sBasket;
use Shopware\Models\Order\Order;
use Shopware_Components_Modules;

/**
 * Class AdyenManager
 * @package MeteorAdyen\Components\Manager
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

    /**
     * @var Shopware_Components_Modules
     */
    private $modules;

    public function __construct(
        EntityManagerInterface $modelManager,
        Enlight_Components_Session_Namespace $session,
        Shopware_Components_Modules $modules
    ) {
        $this->modelManager = $modelManager;
        $this->session = $session;
        $this->modules = $modules;
    }

    /**
     * @return Order|null
     */
    public function fetchOrderIdForCurrentSession(): ?Order
    {
        $order = $this->modelManager->getRepository(Order::class)
            ->findOneBy(['temporaryId' => $this->session->get('sessionId')]);

        return $order;
    }

    /**
     * @return \sBasket
     */
    public function getBasket(): sBasket
    {
        return $this->modules->Basket();
    }

    /**
     * @param $paymentData
     */
    public function storePaymentDataInSession($paymentData): void
    {
        $this->session->offsetSet('adyenPaymentData', $paymentData);
    }

    /**
     * @return string
     */
    public function getPaymentDataSession(): string
    {
        return $this->session->offsetGet('adyenPaymentData');
    }
}