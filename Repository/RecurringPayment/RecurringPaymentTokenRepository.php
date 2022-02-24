<?php

declare(strict_types=1);

namespace AdyenPayment\Repository\RecurringPayment;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use AdyenPayment\Models\PaymentResultCode;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

final class RecurringPaymentTokenRepository implements RecurringPaymentTokenRepositoryInterface
{
    private EntityManager $entityManager;
    private EntityRepository $recurringPaymentTokenEntityRepository;

    public function __construct(EntityManager $entityManager, EntityRepository $recurringPaymentTokenEntityRepository)
    {
        $this->entityManager = $entityManager;
        $this->recurringPaymentTokenEntityRepository = $recurringPaymentTokenEntityRepository;
    }

    public function fetchByCustomerIdAndOrderNumber(string $customerId, string $orderNumber): RecurringPaymentToken
    {
        $recurringPaymentToken = $this->recurringPaymentTokenEntityRepository->findOneBy([
            'customerId' => $customerId,
            'orderNumber' => $orderNumber,
        ]);

        if (!($recurringPaymentToken instanceof RecurringPaymentToken)) {
            throw RecurringPaymentTokenNotFoundException::withCustomerIdAndOrderNumber($customerId, $orderNumber);
        }

        return $recurringPaymentToken;
    }

    public function fetchPendingByPspReference(string $pspReference): RecurringPaymentToken
    {
        $recurringPaymentToken = $this->recurringPaymentTokenEntityRepository->findOneBy([
            'resultCode' => PaymentResultCode::pending()->resultCode(),
            'pspReference' => $pspReference,
        ]);

        if (!($recurringPaymentToken instanceof RecurringPaymentToken)) {
            throw RecurringPaymentTokenNotFoundException::withPendingResultCodeAndPspReference($pspReference);
        }

        return $recurringPaymentToken;
    }

    public function update(RecurringPaymentToken $recurringPaymentToken): void
    {
        $this->entityManager->persist($recurringPaymentToken);
        $this->entityManager->flush($recurringPaymentToken);
    }
}
