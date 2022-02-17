<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use AdyenPayment\Exceptions\RecurringPaymentTokenNotSavedException;
use AdyenPayment\Models\PaymentResultCodes;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Shopware\Components\Model\ModelManager;

final class RecurringPaymentTokenRepository implements RecurringPaymentTokenRepositoryInterface
{
    /** @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|RecurringPaymentTokenRepository */
    private $recurringPaymentTokenRepository;

    public function __construct(ModelManager $manager)
    {
        $this->recurringPaymentTokenRepository = $manager->getRepository(RecurringPaymentToken::class);
    }

    public function save(RecurringPaymentToken $recurringPaymentToken): void
    {
        try {
            $this->recurringPaymentTokenRepository->persist($recurringPaymentToken);
            $this->recurringPaymentTokenRepository->flush();
        } catch (ORMException|ORMInvalidArgumentException $exception) {
            throw RecurringPaymentTokenNotSavedException::withId($recurringPaymentToken->tokenIdentifier());
        }
    }

    public function fetchByCustomerIdAndOrderNumber(string $customerId, string $orderNumber): RecurringPaymentToken
    {
        $recurringPaymentToken = $this->recurringPaymentTokenRepository->findBy([
            'customerId' => $customerId,
            'orderNumber' => $orderNumber,
        ]);

        if (!$recurringPaymentToken) {
            throw RecurringPaymentTokenNotFoundException::withCustomerIdAndOrderNumber($customerId, $orderNumber);
        }

        return $recurringPaymentToken;
    }

    public function fetchPendingByPspReference(string $pspReference): RecurringPaymentToken
    {
        $recurringPaymentToken = $this->recurringPaymentTokenRepository->findBy([
            'resultCode' => PaymentResultCodes::pending()->resultCode(),
            'pspReference' => $pspReference,
        ]);

        if (!$recurringPaymentToken) {
            throw RecurringPaymentTokenNotFoundException::withPendingResultCodeAndPspReference($pspReference);
        }

        return $recurringPaymentToken;
    }

    public function update(RecurringPaymentToken $recurringPaymentToken): void
    {
        try {
            $this->recurringPaymentTokenRepository->persist($recurringPaymentToken);
            $this->recurringPaymentTokenRepository->flush();
        } catch (ORMException|ORMInvalidArgumentException $exception) {
            throw RecurringPaymentTokenNotSavedException::withId($recurringPaymentToken->tokenIdentifier());
        }
    }
}
