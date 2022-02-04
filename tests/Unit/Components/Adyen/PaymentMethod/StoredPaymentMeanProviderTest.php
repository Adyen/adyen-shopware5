<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Components\Adyen\PaymentMethod;

use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Components\Adyen\PaymentMethod\StoredPaymentMeanProvider;
use AdyenPayment\Components\Adyen\PaymentMethod\StoredPaymentMeanProviderInterface;
use AdyenPayment\Shopware\Provider\PaymentMeansProviderInterface;
use Enlight_Controller_Request_Request;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

final class StoredPaymentMeanProviderTest extends TestCase
{
    use ProphecyTrait;
    private StoredPaymentMeanProviderInterface $storedPaymentMeanProvider;

    /** @var EnrichedPaymentMeanProviderInterface|ObjectProphecy */
    private $enrichedPaymentMeanProvider;

    /** @var ObjectProphecy|PaymentMeansProviderInterface */
    private $paymentMeansProvider;

    protected function setUp(): void
    {
        $this->enrichedPaymentMeanProvider = $this->prophesize(EnrichedPaymentMeanProviderInterface::class);
        $this->paymentMeansProvider = $this->prophesize(PaymentMeansProviderInterface::class);

        $this->storedPaymentMeanProvider = new StoredPaymentMeanProvider();
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
    public function it_will_return_null_on_none_combined_id_param(): void
    {
        $request = $this->prophesize(Enlight_Controller_Request_Request::class);
        $request->getParam('register', [])->willReturn(['payment' => $id = 'anyPaymentId']);

        $result = $this->storedPaymentMeanProvider->fromRequest($request->reveal());

        self::assertNull($result);
    }

    /** @test */
    public function it_will_return_the_stored_method_id_from_a_combined_id_param(): void
    {
        $request = $this->prophesize(Enlight_Controller_Request_Request::class);
        $request->getParam('register', [])->willReturn(['payment' => 'umbrellaId_'.($expectedId = 'storedMethodId')]);

        $result = $this->storedPaymentMeanProvider->fromRequest($request->reveal());

        self::assertEquals($expectedId, $result);
    }
}
