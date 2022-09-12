<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Components\Adyen\PaymentMethod\TraceableEnrichedPaymentMeanProvider;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class TraceableEnrichedPaymentMeanProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var EnrichedPaymentMeanProviderInterface|ObjectProphecy */
    private $enrichedPaymentMeanProvider;

    /** @var TraceableEnrichedPaymentMeanProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->enrichedPaymentMeanProvider = $this->prophesize(EnrichedPaymentMeanProviderInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->provider = new TraceableEnrichedPaymentMeanProvider(
            $this->enrichedPaymentMeanProvider->reveal(),
            $this->logger->reveal()
        );
    }

    /** @test */
    public function it_provides_enriched_payment_means(): void
    {
        $paymentMeans = new PaymentMeanCollection(
            $paymentMean = PaymentMean::createFromShopwareArray([
                'source' => SourceType::adyen()->getType(),
            ])
        );
        $this->enrichedPaymentMeanProvider->__invoke($paymentMeans)->willReturn(
            $enriched = new PaymentMeanCollection($paymentMean)
        );
        $this->logger->critical(Argument::cetera())->shouldNotBeCalled();

        $result = $this->provider->__invoke($paymentMeans);
        $this->assertSame($enriched, $result);
    }

    /** @test */
    public function it_logs_silently_exceptions(): void
    {
        $paymentMeans = new PaymentMeanCollection(
            $paymentMean = PaymentMean::createFromShopwareArray([
                'source' => SourceType::adyen()->getType(),
            ])
        );
        $this->enrichedPaymentMeanProvider->__invoke($paymentMeans)->willThrow(
            $exception = new \Exception($message = 'invalid type')
        );

        $this->logger->critical($message, ['exception' => $exception])->shouldBeCalled();

        $result = $this->provider->__invoke($paymentMeans);
        $this->assertNotSame($paymentMeans, $result);
        $this->assertInstanceOf(PaymentMeanCollection::class, $result);
        $this->assertCount(0, $result);
    }
}
