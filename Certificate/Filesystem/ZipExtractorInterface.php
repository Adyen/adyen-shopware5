<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;
use AdyenPayment\Certificate\Model\ApplePayCertificate;

interface ZipExtractorInterface
{
    /**
     * @throws CouldNotWriteCertificate
     *
     * @return ApplePayCertificate|void
     */
    public function __invoke();
}
