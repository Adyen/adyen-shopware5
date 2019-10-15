<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Model\ModelManager;

class PaymentMethodService
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var int
     */
    private $adyenId;

    /**
     * PaymentMethodService constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
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
     * @param int $userId
     * @param bool $prependAdyen
     * @return string
     */
    public function getSelectedAdyenMethod(int $userId, $prependAdyen = true)
    {
        $qb = $this->modelManager->getDBALQueryBuilder();
        $qb->select('a.meteor_adyen_payment_method')
            ->from('s_user_attributes', 'a')
            ->where('a.userId = :customerId')
            ->setParameter('customerId', $userId);
        return ($prependAdyen ? 'adyen_' : '') . $qb->execute()->fetchColumn();
    }
}
