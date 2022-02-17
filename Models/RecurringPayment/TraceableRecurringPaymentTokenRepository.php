<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Psr\Log\LoggerInterface;

final class TraceableRecurringPaymentTokenRepository implements RecurringPaymentTokenRepositoryInterface
{
    private RecurringPaymentTokenRepositoryInterface $recurringPaymentTokenRepository;
    private LoggerInterface $logger;

    public function __construct(
        RecurringPaymentTokenRepositoryInterface $recurringPaymentTokenRepository,
        LoggerInterface $logger
    ) {
        $this->recurringPaymentTokenRepository = $recurringPaymentTokenRepository;
        $this->logger = $logger;
    }

    public function save(RecurringPaymentToken $recurringPaymentToken): void
    {
        try {
            $this->recurringPaymentTokenRepository->save($recurringPaymentToken);
        } catch (ORMException|ORMInvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
        }
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
        }
    }
}
