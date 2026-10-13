<?php

use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use Adyen\Core\BusinessLogic\CheckoutAPI\PaymentRequest\Request\StartTransactionRequest;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\MissingActiveApiConnectionData;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\MissingClientKeyConfiguration;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Components\BasketHelper;
use AdyenPayment\Components\CheckoutConfigProvider;
use AdyenPayment\Components\Integration\PaymentMethodService;
use AdyenPayment\Exceptions\PaymentMeanDoesNotExistException;
use AdyenPayment\Utilities\Shop;
use AdyenPayment\Utilities\Url;
use Shopware\Components\BasketSignature\BasketPersister;
use Shopware\Components\BasketSignature\BasketSignatureGeneratorInterface;
use AdyenPayment\Services\CustomerService;

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
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->checkoutConfigProvider = $this->get(CheckoutConfigProvider::class);
        $this->basketHelper = $this->get(BasketHelper::class);
    }

    /**
     * @throws Enlight_Exception
     * @throws InvalidCurrencyCode
     * @throws MissingActiveApiConnectionData
     * @throws MissingClientKeyConfiguration
     * @throws Exception
     */
    public function getCheckoutConfigAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $productNumber = $this->Request()->get('adyen_article_number');
        $shippingAddress = $this->Request()->get('adyenShippingAddress');
        $paymentMethod = $this->Request()->get('adyen_payment_method');

        if ($shippingAddress) {
            $this->handleNewShippingAddress($shippingAddress, $productNumber, $paymentMethod);

            return;
        }

        try {
            $config = $this->checkoutConfigProvider->getExpressCheckoutConfig(
                $this->basketHelper->getTotalAmountFor(
                    $this->prepareCheckoutController(),
                    $productNumber,
                    null,
                    $paymentMethod
                )
            );
        } catch (PaymentMeanDoesNotExistException $e) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode([
                "message" => "This payment method is currently unavailable. " .
                    "Please contact support or try a different payment option."
            ]));
            return;
        }

        $this->Response()->setBody(json_encode($config->toArray()));
    }

    /**
     * @throws Enlight_Exception
     * @throws InvalidCurrencyCode
     * @throws PaymentMeanDoesNotExistException
     * @throws Exception
     */
    public function paypalUpdateOrderAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $paymentData = $this->Request()->get('paymentData');
        $shippingAddress = $this->normalizePayPalShippingAddress($this->Request()->get('shippingAddress'));
        $productNumber = $this->Request()->get('adyen_article_number');
        $pspReference = $this->Request()->get('pspReference');

        if (!$shippingAddress) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode(["message" => "Shipping address is missing"]));

            return;
        }

        if (!$this->isCountryActive($shippingAddress->country)) {
            return;
        }

        try {
            $amount = $this->basketHelper->getTotalAmountFor(
                $this->prepareCheckoutController(),
                $productNumber,
                $shippingAddress,
                'paypal'
            );
        } catch (PaymentMeanDoesNotExistException $e) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode([
                "message" => "PayPal is currently unavailable. Please try again later."
            ]));

            return;
        }

        $response = CheckoutAPI::get()
            ->paymentRequest(Shop::getShopId())->paypalUpdateOrder(
                [
                    'amount' => $amount,
                    'paymentData' => $paymentData,
                    'pspReference' => $pspReference
                ]
            );

        if ($response->getStatus() !== 'success') {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode(["message" => "PayPal order could not be updated"]));

            return;
        }

        $this->Response()->setBody(json_encode(['paymentData' => $response->getPaymentData()]));
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
            Shopware()->Session()->offsetSet('sUserPasswordChangeDate',
                $customer->getPasswordChangeDate()->format('Y-m-d H:i:s'));
        } elseif (!empty($this->Request()->getParam('adyenEmail'))) {
            $customerService->initializeCustomer($this->Request());
        }

        $productNumber = $this->Request()->get('adyen_article_number');
        if (!empty($productNumber)) {
            $this->basketHelper->forceBasketContentFor($productNumber);
        }

        // Finish express checkout with forced payment mean and fresh basket
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = ServiceRegister::getService(ShopPaymentService::class);
        $paymentMean = $paymentMethodService->resolvePaymentMeanByCode(
            $this->Request()->getParam('adyen_payment_method')
        );
        Shopware()->Modules()->Admin()->sUpdatePayment(
            $paymentMean ? $paymentMean->getId() : ''
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
     * @param string|null $paymentMethod
     *
     * @return void
     *
     * @throws InvalidCurrencyCode
     * @throws MissingActiveApiConnectionData
     * @throws MissingClientKeyConfiguration
     * @throws ReflectionException
     */
    private function handleNewShippingAddress(
        $shippingAddress,
        ?string $productNumber = null,
        ?string $paymentMethod = null
    ): void {
        $shippingAddress = json_decode($shippingAddress, false);

        if (!$this->isCountryActive(!empty($shippingAddress->country) ? $shippingAddress->country : null)) {
            return;
        }

        try {
            $config = $this->checkoutConfigProvider->getExpressCheckoutConfig(
                $this->basketHelper->getTotalAmountFor(
                    $this->prepareCheckoutController(),
                    $productNumber,
                    $shippingAddress,
                    $paymentMethod
                )
            );
        } catch (PaymentMeanDoesNotExistException $e) {
            $this->Response()->setHttpResponseCode(400);
            $this->Response()->setBody(json_encode([
                "message" => "This payment method is currently unavailable. " .
                    "Please contact support or try a different payment option."
            ]));
            return;
        }

        $this->Response()->setBody(json_encode(
            [
                'amount' => $config->toArray()['amount']['value'],
                'currency' => $config->toArray()['amount']['currency'],
                'country' => $config->toArray()['countryCode']
            ]
        ));
    }

    /**
     * Checks that the given country is active in the shop; writes a 400 response and returns false otherwise.
     *
     * @param string|null $countryIso
     *
     * @return bool
     */
    private function isCountryActive($countryIso): bool
    {
        /* @var CustomerService $customerService */
        $customerService = ServiceRegister::getService(CustomerService::class);
        if ($countryIso && $customerService->verifyIfCountryIsActive($countryIso)) {
            return true;
        }

        $this->Response()->setHttpResponseCode(400);
        $this->Response()->setBody(json_encode(["message" => "This country is not active"]));

        return false;
    }

    /**
     * Converts the shipping address sent by the PayPal onShippingAddressChange callback
     * ({city, state, postalCode, countryCode}) into the address format used by the plugin ({country, city, zipCode}),
     * so that the dispatch (shipping cost) can be resolved for the selected country.
     *
     * @param array|string|null $shippingAddress
     *
     * @return object|null
     */
    private function normalizePayPalShippingAddress($shippingAddress)
    {
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        if (empty($shippingAddress) || !is_array($shippingAddress)) {
            return null;
        }

        $country = $shippingAddress['countryCode'] ?? $shippingAddress['country'] ?? null;
        if (empty($country)) {
            return null;
        }

        return (object)[
            'country' => strtoupper((string)$country),
            'city' => $shippingAddress['city'] ?? '',
            'zipCode' => $shippingAddress['postalCode'] ?? $shippingAddress['zipCode'] ?? '',
        ];
    }

    /**
     * Starts a basic PayPal guest payment transaction with no customer data.
     *
     * @throws Exception
     */
    private function startGuestPayPalPaymentTransaction()
    {
        $basket = Shopware()->Modules()->Basket()->sGetBasket();

        // Sign the basket with the session user, exactly like Shopware's persistBasket() does, so that
        // verifyBasketSignature() in handleAdditionalData passes for fast-login (guest) sessions that
        // already have an sUserId. Anonymous sessions skip the verification, so a unique id is used
        // there to avoid signature collisions between concurrent shoppers with identical baskets.
        $customerId = Shopware()->Session()->get('sUserId') ?: uniqid('adyen_guest', true);

        /** @var BasketSignatureGeneratorInterface $signatureGenerator */
        $signatureGenerator = $this->get('basket_signature_generator');
        $basketSignature = $signatureGenerator->generateSignature($basket, $customerId);

        // Shopware's loadBasketFromSignature() replaces the session order variables with the persisted data and
        // verifyBasketSignature() reads the basket from its 'sBasket' key, so persist the current order variables
        // (like Shopware's persistBasket() does) with the fresh basket.
        $orderVariables = Shopware()->Session()->offsetGet('sOrderVariables');
        $orderVariables = $orderVariables instanceof ArrayObject ? $orderVariables->getArrayCopy() : [];
        $orderVariables['sBasket'] = $basket;

        /** @var BasketPersister $persister */
        $persister = $this->get('basket_persister');
        $persister->persist($basketSignature, $orderVariables);

        $reference = md5(uniqid("{$basketSignature}_"));
        $productNumber = $this->Request()->get('adyen_article_number');

        $response = CheckoutAPI::get()
            ->paymentRequest(Shop::getShopId())
            ->startTransaction(
                new StartTransactionRequest(
                    'paypal',
                    $this->basketHelper->getTotalAmountFor(
                        $this->prepareCheckoutController(),
                        !empty($productNumber) ? $productNumber : null,
                        null,
                        'paypal'
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

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function prepareCheckoutController(): Shopware_Controllers_Frontend_Checkout
    {
        /** @var Shopware_Controllers_Frontend_Checkout $checkoutController */
        $checkoutController = Enlight_Class::Instance(Shopware_Controllers_Frontend_Checkout::class,
            [$this->request, $this->response]);
        $checkoutController->init();
        $checkoutController->setView($this->View());
        $checkoutController->setContainer($this->container);
        $checkoutController->setFront($this->front);
        $checkoutController->setRequest($this->request);
        $checkoutController->setResponse($this->response);

        return $checkoutController;
    }
}
