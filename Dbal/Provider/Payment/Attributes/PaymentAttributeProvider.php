<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Provider\Payment\Attributes;

use Enlight_Components_Db_Adapter_Pdo_Mysql;

final class PaymentAttributeProvider
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $adyenType
     * @return int | null
     */
    public function fetchPaymentMeanIdByAdyenType(string $adyenType)
    {
        if ('' === $adyenType) {
            return null;
        }

        $paymentAttributesResult = $this->db
            ->select()
            ->from('s_core_paymentmeans_attributes')
            ->where('adyen_type=?', $adyenType)
            ->query()
            ->fetchAll();
        return $paymentAttributesResult[0]['paymentmeanID'] ?? null;
    }
}