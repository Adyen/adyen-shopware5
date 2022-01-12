<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Decoder;

use AdyenPayment\Certificate\Decoder\ApplePayCertificateDecoder;
use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Response\ApplePayResponseInterface;
use GuzzleHttp\Psr7\Response;
use Phpro\HttpTools\Encoding\DecoderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ApplePayCertificateDecoderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|ZipExtractorInterface */
    private $zipExtractor;

    /** @var CertificateWriterInterface|ObjectProphecy */
    private $certificateWriter;

    /** @var ApplePayResponseInterface|ObjectProphecy */
    private $applePayResponse;
    private ApplePayCertificateDecoder $applePayCertificateDecoder;

    protected function setUp(): void
    {
        $this->zipExtractor = $this->prophesize(ZipExtractorInterface::class);
        $this->certificateWriter = $this->prophesize(CertificateWriterInterface::class);
        $this->applePayResponse = $this->prophesize(ApplePayResponseInterface::class);

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
        $response = new Response(200, [], '');

        $this->zipExtractor->__invoke()->shouldBeCalledOnce();

        ($this->applePayCertificateDecoder)($response);
    }

    /** @test */
    public function it_uses_fallback_zip_when_response_is_not_ok(): void
    {
        $this->zipExtractor->__invoke()->shouldBeCalledOnce();

        $response = new Response(403, [], 'test');

        ($this->applePayCertificateDecoder)($response);
    }

    /** @test */
    public function it_uses_adyen_apple_pay_certificate_from_response(): void
    {
        $this->certificateWriter->__invoke(
            $content = 'apple pay certificate from adyen'
        )->shouldBeCalledOnce();

        $response = new Response(200, [], $content);

        ($this->applePayCertificateDecoder)($response);
    }
}
