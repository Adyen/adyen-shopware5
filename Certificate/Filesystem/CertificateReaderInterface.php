<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Model\ApplePayCertificate;

interface CertificateReaderInterface
{
    public function __invoke(): ApplePayCertificate;
}
