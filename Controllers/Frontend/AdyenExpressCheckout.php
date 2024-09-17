<?php

use Adyen\Core\BusinessLogic\AdminAPI\Response\Response;
use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use Adyen\Core\BusinessLogic\CheckoutAPI\PaymentRequest\Request\StartTransactionRequest;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Components\BasketHelper;
use AdyenPayment\Components\CheckoutConfigProvider;
use AdyenPayment\Utilities\Shop;
use AdyenPayment\Utilities\Url;
use Shopware\Components\BasketSignature\BasketPersister;
use Shopware\Components\BasketSignature\BasketSignatureGeneratorInterface;
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

    /**
     * @throws Enlight_Exception
     * @throws InvalidCurrencyCode
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function getCheckoutConfigAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $productNumber = $this->Request()->get('adyen_article_number');
        $shippingAddress = $this->Request()->get('adyenShippingAddress');

        if ($shippingAddress) {
            $this->handleNewShippingAddress($shippingAddress, $productNumber);

            return;
        }

        $this->Response()->setBody(json_encode(
            $this->checkoutConfigProvider->getExpressCheckoutConfig(
                $this->basketHelper->getTotalAmountFor($this->prepareCheckoutController(), $productNumber)
            )->toArray()
        ));
    }

    public function paypalUpdateOrderAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $paymentData = $this->Request()->get('paymentData');
        $shippingAddress = $this->Request()->get('shippingAddress');
        $productNumber = $this->Request()->get('adyen_article_number');
        $pspReference = $this->Request()->get('pspReference');

        if ($shippingAddress) {
            $amount = $this->basketHelper->getTotalAmountFor(
                $this->prepareCheckoutController(),
                $productNumber,
                $shippingAddress
            );

            $response = CheckoutAPI::get()
                ->paymentRequest(Shop::getShopId())->paypalUpdateOrder(
                    [
                        'amount' => $amount,
                        'paymentData' => $paymentData,
                        'pspReference' => $pspReference
                    ]
                );

            if ($response->getStatus() === 'success') {
                $this->Response()->setBody(json_encode(['paymentData' => $response->getPaymentData()]));
            }
        }
    }

    /**
     * Main entry point for express checkout processing when Adyen express checkout payment is confirmed on frontend
     *
     * @return void
     * @throws Exception
     */
    public function finishAction(): void
    {
        /* @var CustomerService $customerService */
        $customerService = ServiceRegister::getService(CustomerService::class);

        if (!$customerService->isUserLoggedIn() && !empty($this->Request()->getParam('adyenEmail'))) {
            $customer = $customerService->initializeCustomer($this->Request());

            $this->front->Request()->setPost('email', $customer->getEmail());
            $this->front->Request()->setPost('passwordMD5', $customer->getPassword());
            Shopware()->Modules()->Admin()->sLogin(true);

            Shopware()->Session()->offsetSet('sUserId', $customer->getId());
            Shopware()->Session()->offsetSet('sUserMail', $customer->getEmail());
            Shopware()->Session()->offsetSet('sUserGroup', $customer->getGroup()->getKey());
            Shopware()->Session()->offsetSet('sUserPasswordChangeDate', $customer->getPasswordChangeDate()->format('Y-m-d H:i:s'));
        } elseif (!empty($this->Request()->getParam('adyenEmail'))) {
            $customerService->initializeCustomer($this->Request());
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
        /* @var CustomerService $customerService */
        $customerService = ServiceRegister::getService(CustomerService::class);

        if (
            !$customerService->isUserLoggedIn()
            && PaymentMethodCode::payPal()->equals($this->Request()->getParam('adyen_payment_method'))
            && empty($this->Request()->getParam('adyenEmail'))
        ) {
            $this->startGuestPayPalPaymentTransaction();

            return;
        }

        // Make sure that checkout session data is updated as if confirm view was rendered
        $coController->confirmAction();

        // Simulate order confirmation button click server-side logic (this will redirect tot the payment URL and standard payment processing logic)
        $coController->Request()->setParam('sAGB', true);
        $coController->Request()->setParam('esdAgreementChecked', true);
        $coController->Request()->setParam('serviceAgreementChecked', true);
        $coController->paymentAction();
    }

    /**
     * @param $shippingAddress
     * @param string|null $productNumber
     *
     * @return void
     *
     * @throws InvalidCurrencyCode
     */
    private function handleNewShippingAddress($shippingAddress, ?string $productNumber = null): void
    {
        $shippingAddress = json_decode($shippingAddress, false);

        /* @var CustomerService $customerService */
        $customerService = ServiceRegister::getService(CustomerService::class);
        if (!$customerService->verifyIfCountryIsActive($shippingAddress->country)) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode(["message" => "Invalid country code"]));

            return;
        }

        $config = $this->checkoutConfigProvider->getExpressCheckoutConfig(
            $this->basketHelper->getTotalAmountFor(
                $this->prepareCheckoutController(),
                $productNumber,
                $shippingAddress
            )
        );
        $this->Response()->setBody(json_encode(
            [
                'amount' => $config->toArray()['amount']['value'],
                'currency' => $config->toArray()['amount']['currency'],
                'country' => $config->toArray()['countryCode']
            ]
        ));
    }

    /**
     * Starts a basic PayPal guest payment transaction with no customer data.
     *
     * @throws Exception
     */
    private function startGuestPayPalPaymentTransaction()
    {
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        /** @var BasketSignatureGeneratorInterface $signatureGenerator */
        $signatureGenerator = $this->get('basket_signature_generator');
        $basketSignature = $signatureGenerator->generateSignature($basket, uniqid('adyen_guest', true));

        /** @var BasketPersister $persister */
        $persister = $this->get('basket_persister');
        $persister->persist($basketSignature, $basket);

        $reference = md5(uniqid("{$basketSignature}_"));
        $productNumber = $this->Request()->get('adyen_article_number');

        $response = CheckoutAPI::get()
            ->paymentRequest(Shop::getShopId())
            ->startTransaction(
                new StartTransactionRequest(
                    'paypal',
                    $this->basketHelper->getTotalAmountFor(
                        $this->prepareCheckoutController(),
                        !empty($productNumber) ? $productNumber : null
                    ),
                    $reference,
                    Url::getFrontUrl(
                        'AdyenPaymentProcess',
                        'handleRedirect',
                        ['signature' => $basketSignature, 'reference' => $reference]
                    ),
                    (array)json_decode(Shopware()->Session()->offsetGet('adyenPaymentMethodStateData'), true),
                    [
                        'basket' => $this->getBasket(),
                    ]
                )
            );

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $this->Response()->setBody(
            json_encode([
                'action' => $response->getAction(),
                'signature' => $basketSignature,
                'reference' => $reference,
                'pspReference' => $response->getPspReference()
            ])
        );
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
}
