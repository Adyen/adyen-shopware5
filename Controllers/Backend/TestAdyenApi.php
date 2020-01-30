<?php

use MeteorAdyen\Components\Adyen\PaymentMethodService;

class Shopware_Controllers_Backend_TestAdyenApi extends Shopware_Controllers_Backend_ExtJs
{
    /** @var PaymentMethodService */
    private $paymentMethodService;

    public function runAction()
    {
        $this->paymentMethodService = $this->get('meteor_adyen.components.adyen.payment.method');

        try {
            $this->paymentMethodService->getPaymentMethods('BE', 'EUR', 20, false);

            $this->View()->assign('responseText', 'Adyen API connected');
        } catch (Exception $e) {
            $this->View()->assign('responseText', $e->getMessage());
        }
    }
}