<?php

namespace AdyenPayment\Components;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Doctrine\DBAL\Connection;
use Enlight_Components_Session_Namespace;
use sBasket;
use Shopware_Controllers_Frontend_Checkout;

/**
 * Class BasketHelper
 *
 * @package AdyenPayment\Components
 */
class BasketHelper
{
    /**
     * @var sBasket
     */
    private $basket;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(Connection $connection, Enlight_Components_Session_Namespace $session)
    {
        $this->basket = Shopware()->Modules()->Basket();
        $this->connection = $connection;
        $this->session = $session;
    }

    public function forceBasketContentFor(string $articleOrderNumber): void
    {
        $this->basket->sDeleteBasket();
        $this->basket->sAddArticle($articleOrderNumber);
        $this->basket->sRefreshBasket();
    }

    public function getTotalAmountFor(
        Shopware_Controllers_Frontend_Checkout $coController,
        ?string $articleOrderNumber = null
    ): Amount {
        if (!$articleOrderNumber) {
            return $this->getCurrentCartAmount($coController);
        }

        $this->backupCurrentBasket();
        $this->forceBasketContentFor($articleOrderNumber);
        $totalAmount = $this->getCurrentCartAmount($coController);
        $this->restoreBasketFromBackup();


        return $totalAmount;
    }

    private function getCurrentCartAmount(Shopware_Controllers_Frontend_Checkout $coController): Amount
    {
        $basket = $coController->getBasket();
        $totalAmount = array_key_exists('sAmountWithTax', $basket) ? $basket['sAmountWithTax'] : $basket['sAmount'];
        $currencyName = Shopware()->Shop() ? Shopware()->Shop()->getCurrency()->getCurrency() : null;

        return Amount::fromFloat(
            $totalAmount,
            $currencyName ? Currency::fromIsoCode($currencyName) : Currency::getDefault()
        );
    }

    private function backupCurrentBasket(): void
    {
        $this->connection->update(
            's_order_basket',
            ['sessionID' => $this->session->get('sessionId') . '_adyen_backup'],
            ['sessionID' => $this->session->get('sessionId')]
        );
    }

    private function restoreBasketFromBackup(): void
    {
        $this->basket->sDeleteBasket();

        $this->connection->update(
            's_order_basket',
            ['sessionID' => $this->session->get('sessionId')],
            ['sessionID' => $this->session->get('sessionId') . '_adyen_backup']
        );

        $this->basket->sRefreshBasket();
    }
}
