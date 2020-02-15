<?php

use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_TestAdyenApi extends Shopware_Controllers_Backend_ExtJs
{
    public function runAction()
    {
        $paymentMethodService = $this->get('meteor_adyen.components.adyen.payment.method');
        $configuration = $this->get('meteor_adyen.components.configuration');

        try {
            if (empty($configuration->getApiKey()) || empty($configuration->getMerchantAccount())) {
                $this->response->setHttpResponseCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                $this->View()->assign('responseText',
                    "Missing API configuration. Save the configuration form before testing");
            } else {
                $paymentMethodService->getPaymentMethods('BE', 'EUR', 20, false);
                $this->View()->assign('responseText', 'Adyen API connected');
            }
        } catch (Exception $e) {
            $this->get('meteor_adyen.logger')->error($e);
            $this->response->setHttpResponseCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->View()->assign('responseText', $e->getMessage());
        }
    }
}