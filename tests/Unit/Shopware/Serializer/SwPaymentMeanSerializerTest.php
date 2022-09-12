<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Shopware\Serializer;

use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Serializer\PaymentMeanSerializer;
use AdyenPayment\Shopware\Serializer\SwPaymentMeanSerializer;
use PHPUnit\Framework\TestCase;

final class SwPaymentMeanSerializerTest extends TestCase
{
    /** @var SwPaymentMeanSerializer */
    private $serializer;

    protected function setUp(): void
    {
        $this->serializer = new SwPaymentMeanSerializer();
    }

    /** @test */
    public function it_is_a_payment_mean_collection_serializer(): void
    {
        $this->assertInstanceOf(PaymentMeanSerializer::class, $this->serializer);
    }

    /** @test */
    public function it_can_serialize(): void
    {
        $paymentMean = PaymentMean::createFromShopwareArray($raw = [
            'id' => $id = 15,
            'source' => null,
            'name' => 'invoice',
            'description' => 'Rechnung',
            'additionaldescription' => 'additional',
        ]);

        $result = ($this->serializer)($paymentMean);
        $this->assertEquals([$id => $raw], $result);
    }

    /** @test */
    public function it_can_serialize_html(): void
    {
        $paymentMean = PaymentMean::createFromShopwareArray($raw = [
            'id' => $id = 7845,
            'source' => $source = 1,
            'name' => '<some-tag> a name</some-tag> "quoted"',
            'description' => "description and<a href='test'>a link</a>",
            'additionaldescription' => "additional <div>a div</div> and <a href='test'>link</a>",
        ]);

        $result = ($this->serializer)($paymentMean);
        $this->assertEquals([
            $id => [
                'id' => $id,
                'source' => $source,
                'name' => '<some-tag> a name</some-tag> "quoted"',
                'description' => "description and<a href='test'>a link</a>",
                'additionaldescription' => "additional <div>a div</div> and <a href='test'>link</a>",
            ],
        ], $result);
    }
}
