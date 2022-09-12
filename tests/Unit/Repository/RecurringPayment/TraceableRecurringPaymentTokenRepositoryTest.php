<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Repository\RecurringPayment;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use AdyenPayment\Exceptions\RecurringPaymentTokenNotSavedException;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use AdyenPayment\Models\TokenIdentifier;
use AdyenPayment\Repository\RecurringPayment\RecurringPaymentTokenRepositoryInterface;
use AdyenPayment\Repository\RecurringPayment\TraceableRecurringPaymentTokenRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

// @TODO: refactor to integration test.
final class TraceableRecurringPaymentTokenRepositoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var RecurringPaymentTokenRepositoryInterface */
    private $traceableRecurringPaymentTokenRepository;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var ObjectProphecy|RecurringPaymentTokenRepositoryInterface */
    private $recurringPaymentTokenRepository;

    protected function setUp(): void
    {
        $this->recurringPaymentTokenRepository = $this->prophesize(RecurringPaymentTokenRepositoryInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->traceableRecurringPaymentTokenRepository = new TraceableRecurringPaymentTokenRepository(
            $this->recurringPaymentTokenRepository->reveal(),
            $this->logger->reveal()
        );
    }

    /** @test */
    public function it_is_a_recurring_payment_token_repository(): void
    {
        $this->assertInstanceOf(
            RecurringPaymentTokenRepositoryInterface::class,
            $this->traceableRecurringPaymentTokenRepository
        );
    }

    /** @test */
    public function it_can_fetch_a_recurring_payment_token_by_customer_id_and_order_number(): void
    {
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);

        $this->recurringPaymentTokenRepository->fetchByCustomerIdAndOrderNumber(
            $customerId = 'customer-id',
            $orderNumber = 'order-number'
        )->willReturn($recurringPaymentToken->reveal());

        $result = $this->traceableRecurringPaymentTokenRepository->fetchByCustomerIdAndOrderNumber(
            $customerId,
            $orderNumber
        );

        self::assertEquals($recurringPaymentToken->reveal(), $result);
    }

    /** @test */
    public function it_will_throw_an_error_on_missing_recurring_payment_token_by_customer_id_and_order_number(): void
    {
        $exception = new RecurringPaymentTokenNotFoundException();
        $this->recurringPaymentTokenRepository->fetchByCustomerIdAndOrderNumber(
            $customerId = 'customer-id',
            $orderNumber = 'order-number'
        )->willThrow($exception);

        $this->logger->info($exception->getMessage(), ['exception' => $exception])->shouldBeCalled();

        self::expectException(RecurringPaymentTokenNotFoundException::class);

        $this->traceableRecurringPaymentTokenRepository->fetchByCustomerIdAndOrderNumber($customerId, $orderNumber);
    }

    /** @test */
    public function it_can_fetch_a_recurring_payment_token_by_psp_reference(): void
    {
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);

        $this->recurringPaymentTokenRepository->fetchPendingByPspReference($pspReference = 'psp-reference')
            ->willReturn($recurringPaymentToken->reveal());

        $result = $this->traceableRecurringPaymentTokenRepository->fetchPendingByPspReference($pspReference);

        self::assertEquals($recurringPaymentToken->reveal(), $result);
    }

    /** @test */
    public function it_will_throw_an_error_on_missing_recurring_payment_token_by_psp_reference(): void
    {
        $exception = new RecurringPaymentTokenNotFoundException();
        $this->recurringPaymentTokenRepository->fetchPendingByPspReference($pspReference = 'psp-reference')
            ->willThrow($exception);

        $this->logger->info($exception->getMessage(), ['exception' => $exception])->shouldBeCalled();

        self::expectException(RecurringPaymentTokenNotFoundException::class);

        $this->traceableRecurringPaymentTokenRepository->fetchPendingByPspReference($pspReference);
    }

    /** @test */
    public function it_can_update_a_recurring_payment_token(): void
    {
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);
        $this->recurringPaymentTokenRepository->update($recurringPaymentToken->reveal())->shouldBeCalled();

        $this->traceableRecurringPaymentTokenRepository->update($recurringPaymentToken->reveal());
    }

    /** @test */
    public function it_can_catch_a_orm_exception_on_updating_a_recurring_payment_token(): void
    {
        $ormException = new ORMException();
        $token = TokenIdentifier::generate();
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);
        $recurringPaymentToken->tokenIdentifier()->willReturn($token);

        $this->recurringPaymentTokenRepository->update($recurringPaymentToken->reveal())->willThrow($ormException);
        $this->logger->error($ormException->getMessage(), ['exception' => $ormException]);

        self::expectException(RecurringPaymentTokenNotSavedException::class);

        $this->traceableRecurringPaymentTokenRepository->update($recurringPaymentToken->reveal());
    }

    /** @test */
    public function it_can_catch_a_orm_invalid_argument_exception_on_updating_a_recurring_payment_token(): void
    {
        $ormException = new ORMInvalidArgumentException();
        $token = TokenIdentifier::generate();
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);
        $recurringPaymentToken->tokenIdentifier()->willReturn($token);

        $this->recurringPaymentTokenRepository->update($recurringPaymentToken->reveal())->willThrow($ormException);
        $this->logger->error($ormException->getMessage(), ['exception' => $ormException]);

        self::expectException(RecurringPaymentTokenNotSavedException::class);

        $this->traceableRecurringPaymentTokenRepository->update($recurringPaymentToken->reveal());
    }
}
