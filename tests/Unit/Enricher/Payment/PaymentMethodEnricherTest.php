<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Enricher\Payment;

use AdyenPayment\Components\Adyen\PaymentMethod\ImageLogoProviderInterface;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricher;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMethod;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware_Components_Snippet_Manager;

final class PaymentMethodEnricherTest extends TestCase
{
    use ProphecyTrait;
    private PaymentMethodEnricher $paymentMethodEnricher;

    /** @var ObjectProphecy|Shopware_Components_Snippet_Manager */
    private $snippets;

    /** @var ImageLogoProviderInterface|ObjectProphecy */
    private $imageLogoProvider;

    protected function setUp(): void
    {
        $this->snippets = $this->prophesize(Shopware_Components_Snippet_Manager::class);
        $this->imageLogoProvider = $this->prophesize(ImageLogoProviderInterface::class);

        $this->paymentMethodEnricher = new PaymentMethodEnricher(
            $this->snippets->reveal(),
            $this->imageLogoProvider->reveal()
        );
    }

    /** @test */
    public function it_is_a_payment_method_enricher(): void
    {
        $this->assertInstanceOf(PaymentMethodEnricherInterface::class, $this->paymentMethodEnricher);
    }

    /** @test */
    public function it_will_enrich_a_payment_method_without_stored_method_data(): void
    {
        $shopwareMethod = [
            'id' => 'shopware-method-id',
            'additionaldescription' => '',
            'image' => '',
        ];
        $paymentMethod = PaymentMethod::fromRaw($rawData = [
            'code' => 'test_method',
            'type' => 'test_type',
        ]);
        $snippetsNamespace = $this->prophesize(\Enlight_Components_Snippet_Namespace::class);
        $snippetsNamespace->get($paymentMethod->adyenType()->type())->willReturn($description = 'Adyen Method');
        $this->snippets->getNamespace('adyen/method/description')->willReturn($snippetsNamespace);
        $this->imageLogoProvider->provideByType($paymentMethod->adyenType()->type())->willReturn($image = 'image');

        $result = ($this->paymentMethodEnricher)($shopwareMethod, $paymentMethod);

        $expected = [
            'id' => 'shopware-method-id',
            'additionaldescription' => $description,
            'image' => $image,
            'enriched' => true,
            'isStoredPayment' => false,
            'isAdyenPaymentMethod' => true,
            'adyenType' => $paymentMethod->adyenType()->type(),
            'metadata' => $rawData,
        ];

        self::assertEquals($expected, $result);
    }

    /** @test */
    public function it_will_enrich_a_payment_method_with_stored_method_data(): void
    {
        $shopwareMethod = ['id' => $shopwareMethodId = 'shopware-method-id'];
        $paymentMethod = PaymentMethod::fromRaw($rawData = [
            'id' => $storedMethodId = 'stored_method_id',
            'name' => $storedMethodName = 'stored method name',
            'code' => 'test_method',
            'type' => 'test_type',
            'lastFour' => $lastFour = '1234',
        ]);
        $snippetsNamespace = $this->prophesize(\Enlight_Components_Snippet_Namespace::class);
        $snippetsNamespace->get($paymentMethod->adyenType()->type())->willReturn($description = 'Stored Method');
        $snippetsNamespace->get('CardNumberEndingOn', $text = 'Card number ending on', true)->willReturn($text);
        $this->snippets->getNamespace('adyen/method/description')->willReturn($snippetsNamespace);
        $this->snippets->getNamespace('adyen/checkout/payment')->willReturn($snippetsNamespace);
        $this->imageLogoProvider->provideByType($paymentMethod->adyenType()->type())->willReturn($image = 'image');

        $result = ($this->paymentMethodEnricher)($shopwareMethod, $paymentMethod);

        $expected = [
            'id' => 'shopware-method-id',
            'additionaldescription' => sprintf('%s%s: %s', $description.' ', $text, $lastFour),
            'image' => $image,
            'enriched' => true,
            'isStoredPayment' => true,
            'isAdyenPaymentMethod' => true,
            'adyenType' => $paymentMethod->adyenType()->type(),
            'metadata' => $rawData,
            'stored_method_umbrella_id' => sprintf('%s_%s', $shopwareMethodId, $storedMethodId),
            'stored_method_id' => $storedMethodId,
            'description' => $storedMethodName,
            'source' => SourceType::adyen()->getType(),
        ];

        self::assertEquals($expected, $result);
    }
}
