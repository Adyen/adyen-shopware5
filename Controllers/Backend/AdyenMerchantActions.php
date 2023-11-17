<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\PaymentLink\Request\CreatePaymentLinkRequest;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Exceptions\InvalidMerchantReferenceException;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Doctrine\ORM\OptimisticLockException;

/**
 * Class Shopware_Controllers_Backend_AdyenMerchantActions
 */
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

    /**
     * @return void
     *
     * @throws InvalidMerchantReferenceException
     * @throws InvalidCurrencyCode
     */
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

    /**
     * @return void
     *
     * @throws InvalidMerchantReferenceException
     * @throws InvalidCurrencyCode
     */
    public function generatePaymentLinkAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $currency = $this->Request()->get('currency');
        $amount = $this->Request()->get('amount');
        $merchantReference = $this->Request()->get('merchantReference');
        if ((float)$amount === 0.0) {
            $order = $this->getOrderService()->getOrderByTemporaryId((string)$merchantReference);
            $amount = $order->getInvoiceAmount();
        }

        $response = AdminAPI::get()->paymentLink($storeId)->createPaymentLink(
            new CreatePaymentLinkRequest($amount, $currency, $merchantReference)
        );

        if (!$response->isSuccessful()) {
            $namespace = Shopware()->Snippets()->getNamespace('backend/adyen/configuration');
            $translatedString = $namespace->get(
                'payment/adyen/paymentlinkrequestfail',
                'Payment link generation failed. Reason: '
            );
            $this->Response()->setHttpResponseCode($response->getStatusCode());
            $this->Response()->setBody($translatedString . $response->toArray()['errorMessage'] ?? '');
        }
    }

    /**
     * @return void
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidMerchantReferenceException
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function generatePaymentLinkNonAdyenOrderAction(): void
    {
        $orderId = $this->Request()->get('orderId');
        $order = $this->getOrderService()->getOrderById((int)$orderId);
        $temporaryId = $order->getTemporaryId();
        $changed = $order->getChanged()->format('Y-m-d H:i:s');

        if (empty($temporaryId)) {
            $order = $this->getOrderService()->setOrderTemporaryId($orderId, $order->getNumber());
            $temporaryId = $order->getNumber();
            $changed = $order->getChanged()->format('Y-m-d H:i:s');
        }

        $response = AdminAPI::get()->paymentLink((string)$order->getShop()->getId())->createPaymentLink(
            new CreatePaymentLinkRequest($order->getInvoiceAmount(), $order->getCurrency(), $order->getTemporaryId())
        );

        if (!$response->isSuccessful()) {
            $namespace = Shopware()->Snippets()->getNamespace('backend/adyen/configuration');
            $translatedString = $namespace->get(
                'payment/adyen/paymentlinkrequestfail',
                'Payment link generation failed. Reason: '
            );
            $this->Response()->setHttpResponseCode($response->getStatusCode());
            $this->Response()->setBody($translatedString . $response->toArray()['errorMessage'] ?? '');

            return;
        }
        $response = $response->toArray();
        $response['temporaryId'] = $temporaryId;
        if(!empty($changed)){
            $response['changed'] = $changed;
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($response));
    }

    /**
     * @return OrderRepository
     */
    private function getOrderService(): OrderRepository
    {
        return Shopware()->Container()->get(OrderRepository::class);
    }
}
