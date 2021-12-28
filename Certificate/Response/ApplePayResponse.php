<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

final class ApplePayResponse
{
    public static function createFromZip(
        string $zip
    ): self {
        dd($zip);
    }
}
