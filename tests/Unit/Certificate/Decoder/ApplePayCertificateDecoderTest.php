<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Decoder;

use AdyenPayment\Certificate\Decoder\ApplePayCertificateDecoder;
use AdyenPayment\Certificate\Service\CertificateWriter;
use AdyenPayment\Certificate\Service\CertificateWriterInterface;
use AdyenPayment\Certificate\Service\ZipExtractor;
use AdyenPayment\Certificate\Service\ZipExtractorInterface;
use GuzzleHttp\Psr7\Response;
use Phpro\HttpTools\Encoding\DecoderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ApplePayCertificateDecoderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|ZipExtractor */
    private $zipExtractor;

    /** @var CertificateWriter|ObjectProphecy */
    private $certificateWriter;
    private ApplePayCertificateDecoder $applePayCertificateDecoder;

    protected function setUp(): void
    {
        $this->zipExtractor = $this->prophesize(ZipExtractorInterface::class);
        $this->certificateWriter = $this->prophesize(CertificateWriterInterface::class);

        $this->applePayCertificateDecoder = new ApplePayCertificateDecoder(
            $this->zipExtractor->reveal(),
            $this->certificateWriter->reveal()
        );
    }

    /** @test */
    public function it_is_a_decoder(): void
    {
        $this->assertInstanceOf(DecoderInterface::class, $this->applePayCertificateDecoder);
    }

    /** @test */
    public function it_uses_fallback_zip_when_body_is_empty(): void
    {
        $this->zipExtractor->__invoke(
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string')
        )->willReturn('zip string');

        $response = new Response(200, [], '');

        $actual = ($this->applePayCertificateDecoder)($response);

        self::assertEquals('zip string', $actual->certificateString());
    }

    /** @test */
    public function it_uses_fallback_zip_when_response_is_not_ok(): void
    {
        $this->zipExtractor->__invoke(
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('string')
        )->willReturn('zip string');

        $response = new Response(403, [], 'test');

        $actual = ($this->applePayCertificateDecoder)($response);

        self::assertEquals('zip string', $actual->certificateString());
    }

    /** @test */
    public function it_uses_adyen_apple_pay_certificate_from_response(): void
    {
        $this->certificateWriter->__invoke(
            Argument::type('string'),
            Argument::type('string'),
            $content = 'apple pay certificate from adyen'
        )->willReturn($content);

        $response = new Response(200, [], $content);

        $actual = ($this->applePayCertificateDecoder)($response);

        self::assertEquals('apple pay certificate from adyen', $actual->certificateString());
    }
}
