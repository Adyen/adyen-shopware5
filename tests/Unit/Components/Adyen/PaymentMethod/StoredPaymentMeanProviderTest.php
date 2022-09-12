<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Components\Adyen\PaymentMethod;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Components\Adyen\PaymentMethod\StoredPaymentMeanProvider;
use AdyenPayment\Components\Adyen\PaymentMethod\StoredPaymentMeanProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ForwardCompatibility\DriverResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use Enlight_Controller_Request_Request;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

final class StoredPaymentMeanProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var StoredPaymentMeanProvider */
    private $storedPaymentMeanProvider;

    /** @var EnrichedPaymentMeanProviderInterface|ObjectProphecy */
    private $enrichedPaymentMeanProvider;

    /** @var Connection|ObjectProphecy */
    private $connection;

    protected function setUp(): void
    {
        $this->enrichedPaymentMeanProvider = $this->prophesize(EnrichedPaymentMeanProviderInterface::class);
        $this->connection = $this->prophesize(Connection::class);

        $this->storedPaymentMeanProvider = new StoredPaymentMeanProvider(
            $this->enrichedPaymentMeanProvider->reveal(),
            $this->connection->reveal()
        );
    }

    /** @test */
    public function it_is_an_stored_payment_mean_provider(): void
    {
        $this->assertInstanceOf(StoredPaymentMeanProviderInterface::class, $this->storedPaymentMeanProvider);
    }

    /** @test */
    public function it_will_return_null_on_missing_params(): void
    {
        $request = $this->prophesize(Enlight_Controller_Request_Request::class);
        $request->getParam('register', [])->willReturn([]);

        $result = $this->storedPaymentMeanProvider->fromRequest($request->reveal());

        self::assertNull($result);
    }

    /** @test */
    public function it_will_try_to_provide_a_payment_by_umbrella_stored_method_id(): void
    {
        $request = $this->prophesize(Enlight_Controller_Request_Request::class);
        $request->getParam('register', [])->willReturn(['payment' => $id = 'stored_method_umbrella_id']);

        $emptyCollection = PaymentMeanCollection::createFromShopwareArray([]);
        $this->enrichedPaymentMeanProvider->__invoke($emptyCollection)->willReturn($emptyCollection);
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->select('*')->willReturn($queryBuilder);
        $queryBuilder->from('s_core_paymentmeans')->willReturn($queryBuilder);
        $queryBuilder->where('name = :umbrellaMethodName')->willReturn($queryBuilder);
        $queryBuilder->setParameter(':umbrellaMethodName', AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE)->willReturn($queryBuilder);
        $driverResultStatement = $this->prophesize(DriverResultStatement::class);
        $driverResultStatement->fetchAll()->willReturn([]);

        $queryBuilder->execute()->willReturn($driverResultStatement->reveal());
        $this->connection->createQueryBuilder()->willReturn($queryBuilder->reveal());

        $result = $this->storedPaymentMeanProvider->fromRequest($request->reveal());

        self::assertNull($result);
    }
}
