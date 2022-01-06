<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Decoder;

use AdyenPayment\Certificate\Decoder\ApplePayCertificateDecoder;
use Monolog\Logger;
use Phpro\HttpTools\Encoding\DecoderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ApplePayCertificateDecoderTest extends TestCase
{
    private LoggerInterface $logger;
//    private DecoderInterface $applePayCertificateDecoder;

    protected function setUp(): void
    {
        $this->logger = new Logger('test_apple');
//        $this->applePayCertificateDecoder = new ApplePayCertificateDecoder($this->logger);
    }

    /** @test */
    public function it_is_a_decoder_interface(): void
    {
//        $this->assertTrue(false);
//        $this->assertInstanceOf(DecoderInterface::class, $this->applePayCertificateDecoder);
    }
}
