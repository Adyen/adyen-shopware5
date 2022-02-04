<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use Enlight_Controller_Request_Request;

final class StoredPaymentMeanProvider implements StoredPaymentMeanProviderInterface
{
    public function fromRequest(Enlight_Controller_Request_Request $request): ?string
    {
        $registerPayment = $request->getParam('register', [])['payment'] ?? null;
        if (null === $registerPayment) {
            return null;
        }

        // at this point the payment param will be the combined id: [SW umbrella ID] + _ + [Adyen stored method ID]
        $splitMethod = explode('_', $registerPayment);

        return 1 < count($splitMethod) ? $splitMethod[1] : null;
    }
}
