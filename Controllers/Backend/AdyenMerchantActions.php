<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Exceptions\InvalidMerchantReferenceException;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

class Shopware_Controllers_Backend_AdyenMerchantActions extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @throws InvalidMerchantReferenceException
     * @throws \Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode
     */
    public function captureAction(): void
    {
        $currency = $this->Request()->get('currency');
        $amount = $this->Request()->get('amount');
        $merchantReference = $this->Request()->get('merchantReference');
        $storeId = $this->Request()->get('storeId');

        $response = AdminAPI::get()->capture($storeId)->handle($merchantReference, $amount, $currency);

        if (!$response->isSuccessful()) {
            $namespace = Shopware()->Snippets()->getNamespace('backend/adyen/configuration');
            $translatedString = $namespace->get(
                'payment/adyen/capturerequestfail',
                'Capture request failed. Please check Adyen configuration. Reason: '
            );
            $this->Response()->setHttpResponseCode($response->getStatusCode());
            $this->Response()->setBody($translatedString . $response->toArray()['errorMessage'] ?? '');
        }
    }

    /**
     * @throws InvalidMerchantReferenceException
     */
    public function cancelAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $merchantReference = $this->Request()->get('merchantReference');

        $response = AdminAPI::get()->cancel($storeId)->handle($merchantReference);

        if (!$response->isSuccessful()) {
            $namespace = Shopware()->Snippets()->getNamespace('backend/adyen/configuration');
            $translatedString = $namespace->get(
                'payment/adyen/cancelrequestfail',
                'Cancel request failed. Please check Adyen configuration. Reason: '
            );
            $this->Response()->setHttpResponseCode($response->getStatusCode());
            $this->Response()->setBody($translatedString . $response->toArray()['errorMessage'] ?? '');
        }
    }

    public function refundAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $currency = $this->Request()->get('currency');
        $amount = $this->Request()->get('amount');
        $merchantReference = $this->Request()->get('merchantReference');

        $response = AdminAPI::get()->refund($storeId)->handle($merchantReference, $amount, $currency);

        if (!$response->isSuccessful()) {
            $namespace = Shopware()->Snippets()->getNamespace('backend/adyen/configuration');
            $translatedString = $namespace->get(
                'payment/adyen/refundrequestfail',
                'Refund request failed. Please check Adyen configuration. Reason: '
            );
            $this->Response()->setHttpResponseCode($response->getStatusCode());
            $this->Response()->setBody($translatedString . $response->toArray()['errorMessage'] ?? '');
        }
    }
}
