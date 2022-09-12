<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Repository\RecurringPayment;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use AdyenPayment\Models\PaymentResultCode;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use AdyenPayment\Repository\RecurringPayment\RecurringPaymentTokenRepository;
use AdyenPayment\Repository\RecurringPayment\RecurringPaymentTokenRepositoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

// @TODO: refactor to integration test.
final class RecurringPaymentTokenRepositoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var RecurringPaymentTokenRepositoryInterface */
    private $recurringPaymentTokenRepository;

    /** @var EntityManager|ObjectProphecy */
    private $entityManager;

    /** @var EntityRepository|ObjectProphecy */
    private $recurringPaymentTokenEntityRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->recurringPaymentTokenEntityRepository = $this->prophesize(EntityRepository::class);
        $this->recurringPaymentTokenRepository = new RecurringPaymentTokenRepository(
            $this->entityManager->reveal(),
            $this->recurringPaymentTokenEntityRepository->reveal()
        );
    }

    /** @test */
    public function it_is_a_recurring_payment_token_repository(): void
    {
        $this->assertInstanceOf(RecurringPaymentTokenRepositoryInterface::class, $this->recurringPaymentTokenRepository);
    }

    /** @test */
    public function it_can_fetch_a_recurring_payment_token_by_customer_id_and_order_number(): void
    {
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);

        $this->recurringPaymentTokenEntityRepository->findOneBy([
            'customerId' => $customerId = 'customer-id',
            'orderNumber' => $orderNumber = 'order-number',
        ])->willReturn($recurringPaymentToken->reveal());

        $result = $this->recurringPaymentTokenRepository->fetchByCustomerIdAndOrderNumber($customerId, $orderNumber);

        self::assertEquals($recurringPaymentToken->reveal(), $result);
    }

    /** @test */
    public function it_will_throw_an_error_on_missing_recurring_payment_token_by_customer_id_and_order_number(): void
    {
        $this->recurringPaymentTokenEntityRepository->findOneBy([
            'customerId' => $customerId = 'customer-id',
            'orderNumber' => $orderNumber = 'order-number',
        ])->willReturn(null);

        self::expectException(RecurringPaymentTokenNotFoundException::class);

        $this->recurringPaymentTokenRepository->fetchByCustomerIdAndOrderNumber($customerId, $orderNumber);
    }

    /** @test */
    public function it_can_fetch_a_recurring_payment_token_by_psp_reference(): void
    {
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);

        $this->recurringPaymentTokenEntityRepository->findOneBy([
            'resultCode' => PaymentResultCode::pending()->resultCode(),
            'pspReference' => $pspReference = 'psp-reference',
        ])->willReturn($recurringPaymentToken->reveal());

        $result = $this->recurringPaymentTokenRepository->fetchPendingByPspReference($pspReference);

        self::assertEquals($recurringPaymentToken->reveal(), $result);
    }

    /** @test */
    public function it_will_throw_an_error_on_missing_recurring_payment_token_by_psp_reference(): void
    {
        $this->recurringPaymentTokenEntityRepository->findOneBy([
            'resultCode' => PaymentResultCode::pending()->resultCode(),
            'pspReference' => $pspReference = 'psp-reference',
        ])->willReturn(null);

        self::expectException(RecurringPaymentTokenNotFoundException::class);

        $this->recurringPaymentTokenRepository->fetchPendingByPspReference($pspReference);
    }

    /** @test */
    public function it_can_update_a_recurring_payment_token(): void
    {
        $recurringPaymentToken = $this->prophesize(RecurringPaymentToken::class);
        $this->entityManager->persist($recurringPaymentToken->reveal())->shouldBeCalled();
        $this->entityManager->flush($recurringPaymentToken->reveal())->shouldBeCalled();

        $this->recurringPaymentTokenRepository->update($recurringPaymentToken->reveal());
    }
}
