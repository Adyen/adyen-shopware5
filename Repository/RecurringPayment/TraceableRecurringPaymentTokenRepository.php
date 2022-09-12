<?php

declare(strict_types=1);

namespace AdyenPayment\Repository\RecurringPayment;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use AdyenPayment\Exceptions\RecurringPaymentTokenNotSavedException;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Psr\Log\LoggerInterface;

final class TraceableRecurringPaymentTokenRepository implements RecurringPaymentTokenRepositoryInterface
{
    /** @var RecurringPaymentTokenRepositoryInterface */
    private $recurringPaymentTokenRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RecurringPaymentTokenRepositoryInterface $recurringPaymentTokenRepository,
        LoggerInterface $logger
    ) {
        $this->recurringPaymentTokenRepository = $recurringPaymentTokenRepository;
        $this->logger = $logger;
    }

    public function fetchByCustomerIdAndOrderNumber(string $customerId, string $orderNumber): RecurringPaymentToken
    {
        try {
            return $this->recurringPaymentTokenRepository->fetchByCustomerIdAndOrderNumber($customerId, $orderNumber);
        } catch (RecurringPaymentTokenNotFoundException $exception) {
            $this->logger->info($exception->getMessage(), ['exception' => $exception]);

            throw $exception;
        }
    }

    public function fetchPendingByPspReference(string $pspReference): RecurringPaymentToken
    {
        try {
            return $this->recurringPaymentTokenRepository->fetchPendingByPspReference($pspReference);
        } catch (RecurringPaymentTokenNotFoundException $exception) {
            $this->logger->info($exception->getMessage(), ['exception' => $exception]);

            throw $exception;
        }
    }

    public function update(RecurringPaymentToken $recurringPaymentToken): void
    {
        try {
            $this->recurringPaymentTokenRepository->update($recurringPaymentToken);
        } catch (ORMException|ORMInvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            throw RecurringPaymentTokenNotSavedException::withId($recurringPaymentToken->tokenIdentifier());
        }
    }
}
