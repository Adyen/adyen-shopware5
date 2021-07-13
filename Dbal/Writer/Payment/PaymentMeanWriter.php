<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Writer\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Payment\Payment;

final class PaymentMeanWriter implements PaymentMeanWriterInterface
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;
    /**
     * @var ModelRepository
     */
    private $paymentRepository;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ModelRepository $paymentRepository
    )
    {
        $this->db = $db;
        $this->paymentRepository = $paymentRepository;
    }

    public function updateAdyenPaymentMethodBySubshopId(int $subshopId)
    {
        $adyenPaymentMethods = $this->paymentRepository->findBy(
            ['source' => SourceType::adyenType()->getType()]
        );

        /** @var Payment $adyenPaymentMethod */
        foreach ($adyenPaymentMethods as $adyenPaymentMethod) {
            $result = $this->db->executeQuery(
                'INSERT INTO s_core_paymentmeans_subshops (paymentID, subshopID)
                        VALUES (:paymentID, :subshopID)',
                [
                    ':paymentID' => $adyenPaymentMethod->getId(),
                    ':subshopID' => $subshopId
                ]
            );
        }
    }
}
