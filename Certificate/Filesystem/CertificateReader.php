<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotReadCertificate;
use AdyenPayment\Certificate\Model\ApplePayCertificate;

final class CertificateReader implements CertificateReaderInterface
{
    /**
     * @throws CouldNotReadCertificate
     */
    public function __invoke(): ApplePayCertificate
    {
        $path = '.well-known'.'/'.'apple-developer-merchantid-domain-association';
        $fileContent = false;
        if (file_exists($path)) {
            $fileContent = file_get_contents(
                '.well-known'.'/'.'apple-developer-merchantid-domain-association'
            );
        }

        if (!$fileContent) {
            throw CouldNotReadCertificate::withFilepath('.well-known'.'/'.'apple-developer-merchantid-domain-association');
        }

        return ApplePayCertificate::create($fileContent);
    }
}
