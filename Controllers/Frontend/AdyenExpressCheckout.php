<?php

use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Components\BasketHelper;
use AdyenPayment\Components\CheckoutConfigProvider;
use Shopware\Models\Customer\Customer;
use AdyenPayment\Services\CustomerService;
use AdyenPayment\Utilities\Plugin;

/**
 * Class Shopware_Controllers_Frontend_AdyenExpressCheckout
 *
 * The main entry point for Adyen payment processing.
 */
class Shopware_Controllers_Frontend_AdyenExpressCheckout extends Shopware_Controllers_Frontend_Payment
{
    /**
     * @var CheckoutConfigProvider
     */
    private $checkoutConfigProvider;
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function preDispatch(): void
    {
        $this->checkoutConfigProvider = $this->get(CheckoutConfigProvider::class);
        $this->basketHelper = $this->get(BasketHelper::class);
    }

    public function getCheckoutConfigAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $productNumber = $this->Request()->get('adyen_article_number');

        $this->Response()->setBody(json_encode(
            $this->checkoutConfigProvider->getExpressCheckoutConfig(
                $this->basketHelper->getTotalAmountFor($this->prepareCheckoutController(), $productNumber)
            )->toArray()
        ));
    }

    /**
     * Main entry point for express checkout processing when Adyen express checkout payment is confirmed on frontend
     *
     * @return void
     * @throws Exception
     */
    public function finishAction(): void
    {
        // Finish express checkout with forced payment mean and fresh basket
        if (!$this->isUserLoggedIn()) {
            $this->initializeCustomer();
        }

        $productNumber = $this->Request()->get('adyen_article_number');
        if (!empty($productNumber)) {
            $this->basketHelper->forceBasketContentFor($productNumber);
        }

        // Finish express checkout with forced payment mean and fresh basket
        $paymentMean = Shopware()->Modules()->Admin()->sGetPaymentMean(
            Plugin::getPaymentMeanName($this->Request()->getParam('adyen_payment_method'))
        );
        Shopware()->Modules()->Admin()->sUpdatePayment(
            $paymentMean['id'] ?? ''
        );

        Shopware()->Session()->offsetSet(
            'adyenPaymentMethodStateData',
            $this->Request()->get('adyenExpressPaymentMethodStateData')
        );
        Shopware()->Session()->offsetSet(
            'adyenIsXHR',
            $this->Request()->getParam('isXHR')
        );

        $coController = $this->prepareCheckoutController();
        $coController->preDispatch();

        // Make sure that checkout session data is updated as if confirm view was rendered
        $coController->confirmAction();

        // Simulate order confirmation button click server-side logic (this will redirect tot the payment URL and standard payment processing logic)
        $coController->Request()->setParam('sAGB', true);
        $coController->Request()->setParam('esdAgreementChecked', true);
        $coController->Request()->setParam('serviceAgreementChecked', true);
        $coController->paymentAction();
    }

    private function prepareCheckoutController(): Shopware_Controllers_Frontend_Checkout
    {
        /** @var Shopware_Controllers_Frontend_Checkout $checkoutController */
        $checkoutController = Enlight_Class::Instance(Shopware_Controllers_Frontend_Checkout::class, [$this->request, $this->response]);
        $checkoutController->init();
        $checkoutController->setView($this->View());
        $checkoutController->setContainer($this->container);
        $checkoutController->setFront($this->front);
        $checkoutController->setRequest($this->request);
        $checkoutController->setResponse($this->response);

        return $checkoutController;
    }

    /**
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws Exception
     */
    private function initializeCustomer()
    {
        $email = str_replace(['"', "'"], '', $this->Request()->getParam('adyenEmail'));
        $address = json_decode($this->Request()->getParam('adyenAddress'));

        if ($email && $address) {
            /* @var CustomerService $customerService */
            $customerService = ServiceRegister::getService(CustomerService::class);
            $customer = $customerService->getCustomerByEmail($email);

            if (!$customer) {
                $customer = $customerService->createCustomer($email, $address);
            }

            $this->front->Request()->setPost('email', $customer->getEmail());
            $this->front->Request()->setPost('passwordMD5', $customer->getPassword());
            Shopware()->Modules()->Admin()->sLogin(true);

            Shopware()->Session()->offsetSet('sUserId', $customer->getId());
            Shopware()->Session()->offsetSet('sUserMail', $customer->getEmail());
            Shopware()->Session()->offsetSet('sUserGroup', $customer->getGroup()->getKey());
            Shopware()->Session()->offsetSet('sUserPasswordChangeDate', $customer->getPasswordChangeDate()->format('Y-m-d H:i:s'));
        }
    }

    private function isUserLoggedIn(): bool
    {
        if (!(bool)Shopware()->Session()->get('sUserId')) {
            return false;
        }

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (
            !empty($userData['additional']['user']['accountmode']) &&
            (int)$userData['additional']['user']['accountmode'] === Customer::ACCOUNT_MODE_FAST_LOGIN
        ) {
            return false;
        }

        return true;
    }

}
