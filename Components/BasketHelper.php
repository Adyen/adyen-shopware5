<?php

namespace AdyenPayment\Components;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Components\Integration\PaymentMethodService;
use AdyenPayment\Exceptions\PaymentMeanDoesNotExistException;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\NotSupported;
use Shopware\Components\Model\ModelManager;
use Doctrine\DBAL\Connection;
use Enlight_Components_Session_Namespace;
use sBasket;
use Shopware\Models\Country\Country;
use Shopware_Controllers_Frontend_Checkout;

/**
 * Class BasketHelper
 *
 * @package AdyenPayment\Components
 */
class BasketHelper
{
    const ADYEN_NAME_PREFIX = 'adyen_';
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

    /** @var ModelManager */
    private $modelManager;

    public function __construct(
        sBasket $basket,
        Connection $connection,
        Enlight_Components_Session_Namespace $session
    ) {
        $this->basket = $basket;
        $this->connection = $connection;
        $this->session = $session;
        $this->modelManager = Shopware()->Container()->get('models');
    }

    /**
     * @param string $articleOrderNumber
     *
     * @return void
     *
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function forceBasketContentFor(string $articleOrderNumber): void
    {
        $this->basket->sDeleteBasket();
        $this->basket->sAddArticle($articleOrderNumber);
        $this->basket->sRefreshBasket();
    }


    /**
     * @param Shopware_Controllers_Frontend_Checkout $coController
     * @param string|null $articleOrderNumber
     * @param $address
     * @param string|null $paymentMethod
     *
     * @return Amount
     *
     * @throws Exception
     * @throws InvalidCurrencyCode
     * @throws NotSupported
     * @throws PaymentMeanDoesNotExistException
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function getTotalAmountFor(
        Shopware_Controllers_Frontend_Checkout $coController,
        ?string $articleOrderNumber = null,
        $address = null,
        ?string $paymentMethod = null
    ): Amount {
        if (!$articleOrderNumber) {
            if ($address) {
                $this->setDispatchForAddress($address, $paymentMethod);
            }

            return $this->getCurrentCartAmount($coController);
        }

        $this->backupCurrentBasket();
        $this->forceBasketContentFor($articleOrderNumber);

        if ($address) {
            $this->setDispatchForAddress($address, $paymentMethod);
        } elseif (empty($this->session['sDispatch'])) {
            $coController->getSelectedCountry();
            $userData = Shopware()->Modules()->Admin()->sGetUserData();
            $dispatches = Shopware()->Modules()->Admin()->sGetPremiumDispatches(
                (int)$userData["additional"]["countryShipping"]["id"],
                null,
                (int)$userData["additional"]["countryShipping"]["areaID"]
            );
            $dispatch = reset($dispatches);
            $this->session['sDispatch'] = $dispatch ? (int)$dispatch['id'] : 0;
        }

        $totalAmount = $this->getCurrentCartAmount($coController);
        $this->restoreBasketFromBackup();

        return $totalAmount;
    }

    /**
     * @param Shopware_Controllers_Frontend_Checkout $coController
     *
     * @return Amount
     *
     * @throws InvalidCurrencyCode
     */
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

    /**
     * @return void
     *
     * @throws Exception
     */
    private function backupCurrentBasket(): void
    {
        $this->connection->update(
            's_order_basket',
            ['sessionID' => $this->session->get('sessionId') . '_adyen_backup'],
            ['sessionID' => $this->session->get('sessionId')]
        );
    }

    /**
     * @return void
     *
     * @throws Exception
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     * @throws \Zend_Db_Adapter_Exception
     */
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

    /**
     * Sets dispatch for given address country.
     *
     * @param $address
     * @param string|null $paymentMethod
     *
     * @return void
     *
     * @throws NotSupported
     * @throws PaymentMeanDoesNotExistException
     */
    private function setDispatchForAddress($address, ?string $paymentMethod = null)
    {
        $id = null;

        if ($paymentMethod) {
            /** @var PaymentMethodService $service */
            $service = ServiceRegister::getService(ShopPaymentService::class);
            $paymentMean = $service->resolvePaymentMeanByCode($paymentMethod);

            if (!$paymentMean) {
                throw new PaymentMeanDoesNotExistException(
                    'Payment mean with name ' . self::ADYEN_NAME_PREFIX . $paymentMethod . ' does not exist.'
                );
            }

            $id = $paymentMean->getId();
        }

        $countryData = $this->getCountryData($address);
        $dispatches = Shopware()->Modules()->Admin()->sGetPremiumDispatches(
            $countryData['id'],
            $id,
            $countryData['areaId']
        );
        $dispatch = reset($dispatches);
        $this->session['sDispatch'] = $dispatch ? (int)$dispatch['id'] : 0;
    }

    /**
     * Returns country id and area id
     *
     * @param $address
     *
     * @return array|null[]
     * @throws NotSupported
     */
    private function getCountryData($address): array
    {
        /** @var Country $country */
        $country = $this->modelManager->getRepository(Country::class)->findOneBy(['iso' => $address->country]);

        return $country ?
            ['id' => $country->getId(), 'areaId' => $country->getArea() ? $country->getArea()->getId() : null]
            : ['id' => null, 'areaId' => null];
    }
}
