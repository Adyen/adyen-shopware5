<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;
use AdyenPayment\Certificate\Model\ApplePayCertificate;
use Psr\Log\LoggerInterface;

final class TraceableZipExtractorDecorator implements ZipExtractorInterface
{
    private ZipExtractorInterface $zipExtractor;
    private LoggerInterface $logger;

    public function __construct(ZipExtractorInterface $zipExtractor, LoggerInterface $logger)
    {
        $this->zipExtractor = $zipExtractor;
        $this->logger = $logger;
    }

    /**
     * @return ApplePayCertificate|void
     */
    public function __invoke()
    {
        try {
            return ($this->zipExtractor)();
        } catch (CouldNotWriteCertificate $exception) {
            $this->logger->error($exception->getMessage(), [$exception]);
        }
    }
}
