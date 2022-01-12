<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Integration\Certificate\Filesystem;

use AdyenPayment\Certificate\Filesystem\ZipExtractor;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ZipExtractorTest extends TestCase
{
    private $zipExtractor;

    protected function setUp(): void
    {
        $this->zipExtractor = new ZipExtractor();
    }

    /** @test */
    public function it_is_a_zip_extractor(): void
    {
        $this->assertInstanceOf(ZipExtractorInterface::class, $this->zipExtractor);
    }

    /** @test */
    public function it_writes_content_to_file(): void
    {
        $filesystem = new Filesystem();

        $this->zipExtractor->__invoke();

        self::assertTrue($filesystem->exists(__DIR__.'/../../../../.well-known'));
        self::assertStringContainsString(
            '2236304337424642364544314638313934',
            file_get_contents(__DIR__.'/../../../../.well-known/apple-developer-merchantid-domain-association')
        );

        $filesystem->remove([
            '.well-known/apple-developer-merchantid-domain-association',
            '.well-known',
        ]);
    }
}
