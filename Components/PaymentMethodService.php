<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

use Adyen\AdyenException;
use Enlight_Components_Session_Namespace;
use MeteorAdyen\Components\Adyen\PaymentMethodService as AdyenPaymentMethodService;
use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Model\ModelManager;

class PaymentMethodService
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

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
     * @param AdyenPaymentMethodService $adyenPaymentMethodService
     */
    public function __construct(
        ModelManager $modelManager,
        Enlight_Components_Session_Namespace $session,
        AdyenPaymentMethodService $adyenPaymentMethodService
    ) {
        $this->modelManager = $modelManager;
        $this->session = $session;
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
            ->setParameter('name', MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD)
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
        return $this->getUserAdyenMethod((int) $userId, $prependAdyen);
    }

    /**
     * @param int $userId
     * @param bool $prependAdyen
     * @return string
     */
    public function getUserAdyenMethod(int $userId, $prependAdyen = true)
    {
        $qb = $this->modelManager->getDBALQueryBuilder();
        $qb->select('a.meteor_adyen_payment_method')
            ->from('s_user_attributes', 'a')
            ->where('a.userId = :customerId')
            ->setParameter('customerId', $userId);
        return ($prependAdyen ? 'adyen_' : '') . $qb->execute()->fetchColumn();
    }

    /**
     * @param $payment
     * @return bool
     */
    public function isAdyenMethod($payment)
    {
        return substr($payment, 0, 6) === 'adyen_';
    }

    /**
     * @param $type
     * @return mixed
     * @throws AdyenException
     */
    public function getAdyenPaymentDescriptionByType($type)
    {
        $adyenMethods = $this->adyenPaymentMethodService->getPaymentMethods();
        $adyenMethod = null;

        foreach ($adyenMethods['paymentMethods'] as $paymentMethod) {
            if ($paymentMethod['type'] === $type) {
                return $paymentMethod['name'];
            }
        }
    }
}
