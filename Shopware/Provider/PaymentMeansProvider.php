<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Provider;

final class PaymentMeansProvider implements PaymentMeansProviderInterface
{
    public function __invoke(): array
    {
        return Shopware()->Modules()->Admin()->sGetPaymentMeans();
    }
}
