<?php

use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_TestAdyenApi extends Shopware_Controllers_Backend_ExtJs
{
    public function runAction()
    {
        $paymentMethodService = $this->get('adyen_payment.components.adyen.payment.method');
        $configuration = $this->get('adyen_payment.components.configuration');

        $responseText = 'Adyen API failed, check error logs';
        $this->response->setHttpResponseCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        if (empty($configuration->getApiKey()) || empty($configuration->getMerchantAccount())) {
            $this->response->setHttpResponseCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $responseText = 'Missing API configuration. Save the configuration form before testing';
        }

        if (!empty($paymentMethodService->getPaymentMethods('BE', 'EUR', 20, 'nl_NL', false))) {
            $this->response->setHttpResponseCode(Response::HTTP_OK);
            $responseText = 'Adyen API connected';
        }

        $this->View()->assign('responseText', $responseText);
    }
}
