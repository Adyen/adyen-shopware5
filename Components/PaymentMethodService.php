<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Adyen\AdyenException;
use Enlight_Components_Session_Namespace;
use AdyenPayment\Components\Adyen\PaymentMethodService as AdyenPaymentMethodService;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Models\PaymentMethodInfo;
use Shopware\Components\Model\ModelManager;
use Shopware_Components_Snippet_Manager;

/**
 * Class PaymentMethodService
 * @package AdyenPayment\Components
 */
class PaymentMethodService
{

    const PM_LOGO_FILENAME = [
        'scheme' => 'card',
        'yandex_money' => 'yandex'
    ];

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var AdyenPaymentMethodService
     */
    private $adyenPaymentMethodService;

    /**
     * @var int
     */
    private $adyenId;

    /**
     * PaymentMethodService constructor.
     * @param ModelManager $modelManager
     * @param Enlight_Components_Session_Namespace $session
     * @param Shopware_Components_Snippet_Manager $snippetManager
     * @param AdyenPaymentMethodService $adyenPaymentMethodService
     */
    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Session_Namespace $session,
        Shopware_Components_Snippet_Manager $snippetManager,
        AdyenPaymentMethodService $adyenPaymentMethodService
    ) {
        $this->modelManager = $modelManager;
        $this->session = $session;
        $this->snippetManager = $snippetManager;
        $this->adyenPaymentMethodService = $adyenPaymentMethodService;
    }

    /**
     * @return int
     */
    public function getAdyenPaymentId()
    {
        if ($this->adyenId) {
            return (int)$this->adyenId;
        }

        $this->adyenId = $this->modelManager->getDBALQueryBuilder()
            ->select(['id'])
            ->from('s_core_paymentmeans', 'p')
            ->where('name = :name')
            ->setParameter('name', AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD)
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();
        return (int)$this->adyenId;
    }

    /**
     * @param bool $prependAdyen
     * @return string
     */
    public function getActiveUserAdyenMethod($prependAdyen = true)
    {
        $userId = $this->session->offsetGet('sUserId');
        if (empty($userId)) {
            return 'false';
        }
        return $this->getUserAdyenMethod((int)$userId, $prependAdyen);
    }

    /**
     * @param int $userId
     * @param bool $prependAdyen
     * @return string
     */
    public function getUserAdyenMethod(int $userId, $prependAdyen = true)
    {
        $qb = $this->modelManager->getDBALQueryBuilder();
        $qb->select('a.' . AdyenPayment::ADYEN_PAYMENT_PAYMENT_METHOD)
            ->from('s_user_attributes', 'a')
            ->where('a.userId = :customerId')
            ->setParameter('customerId', $userId);
        return ($prependAdyen ? Configuration::PAYMENT_PREFIX : '') . $qb->execute()->fetchColumn();
    }

    /**
     * @param int $userId
     * @param $payment
     * @return bool
     */
    public function setUserAdyenMethod(int $userId, $payment)
    {
        $qb = $this->modelManager->getDBALQueryBuilder();
        $qb->update('s_user_attributes', 'a')
            ->set('a.' . AdyenPayment::ADYEN_PAYMENT_PAYMENT_METHOD, ':payment')
            ->where('a.userId = :customerId')
            ->setParameter('payment', $payment)
            ->setParameter('customerId', $userId)
            ->execute();
    }

    /**
     * @param $payment
     * @return bool
     */
    public function isAdyenMethod($payment)
    {
        return substr((string)$payment, 0, Configuration::PAYMENT_LENGHT) === Configuration::PAYMENT_PREFIX;
    }

    /**
     * @param $payment
     * @return false|string
     */
    public function getAdyenMethod($payment)
    {
        return substr((string)$payment, Configuration::PAYMENT_LENGHT);
    }

    /**
     * @param      $type
     * @param null $paymentMethods
     *
     * @return PaymentMethodInfo
     */
    public function getAdyenPaymentInfoByType($type, $paymentMethods = null): PaymentMethodInfo
    {
        if (!$paymentMethods) {
            $paymentMethodOptions = $this->getPaymentMethodOptions();
            $adyenMethods = $this->adyenPaymentMethodService->getPaymentMethods(
                $paymentMethodOptions['countryCode'],
                $paymentMethodOptions['currency'],
                $paymentMethodOptions['value']
            );

            $paymentMethods = $adyenMethods['paymentMethods'];
        }

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod['type'] === $type) {
                $name = $this->snippetManager
                    ->getNamespace('adyen/method/name')
                    ->get($type, $paymentMethod['name'], true);

                if (empty($name)) {
                    $name = $paymentMethod['name'];
                }

                $description = $this->snippetManager
                    ->getNamespace('adyen/method/description')
                    ->get($type);

                $paymentMethodInfo = (new PaymentMethodInfo())->setName($name);
                if ($description) {
                    $paymentMethodInfo->setDescription($description);
                }

                return $paymentMethodInfo;
            }
        }
        return new PaymentMethodInfo();
    }

    /**
     * @param $adyenMethod
     * @return string
     */
    public function getAdyenImage($adyenMethod)
    {
        $type = $adyenMethod['type'];
        return $this->getAdyenImageByType($type);
    }

    /**
     * @param $type
     * @return string
     */
    public function getAdyenImageByType($type)
    {
        //Some payment method codes don't match the logo filename
        if (!empty(self::PM_LOGO_FILENAME[$type])) {
            $type = self::PM_LOGO_FILENAME[$type];
        }
        return sprintf('https://checkoutshopper-live.adyen.com/checkoutshopper/images/logos/%s.svg', $type);
    }

    public function getPaymentMethodOptions()
    {
        $countryCode = Shopware()->Session()->sOrderVariables['sUserData']['additional']['country']['countryiso'];
        if (!$countryCode) {
            $countryCode = Shopware()->Modules()->Admin()->sGetUserData()['additional']['country']['countryiso'];
        }

        $currency = Shopware()->Session()->sOrderVariables['sBasket']['sCurrencyName'];
        if (!$currency) {
            $currency = Shopware()->Shop()->getCurrency()->getCurrency();
        }

        $value = Shopware()->Session()->sOrderVariables['sBasket']['AmountNumeric'];

        $paymentMethodOptions['countryCode'] = $countryCode;
        $paymentMethodOptions['currency'] = $currency;
        $paymentMethodOptions['value'] = $value ?? 1;

        return $paymentMethodOptions;
    }
}
