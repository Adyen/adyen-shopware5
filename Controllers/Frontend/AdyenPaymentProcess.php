<?php

use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutConfig\Request\DisableStoredDetailsRequest;
use Adyen\Core\BusinessLogic\CheckoutAPI\PaymentRequest\Request\StartTransactionRequest;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\ShopperReference;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\ConnectionSettingsNotFountException;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\CheckoutConfigProvider;
use AdyenPayment\Components\ErrorMessageProvider;
use AdyenPayment\Components\PaymentMeansEnricher;
use AdyenPayment\Services\CustomerService;
use AdyenPayment\Utilities\Plugin;
use AdyenPayment\Utilities\Url;
use AdyenPayment\Utilities\Shop;

/**
 * Class Shopware_Controllers_Frontend_AdyenPaymentProcess
 *
 * The main entry point for Adyen payment processing.
 */
class Shopware_Controllers_Frontend_AdyenPaymentProcess extends Shopware_Controllers_Frontend_Payment
{
    /**
     * @var ErrorMessageProvider
     */
    private $errorMessageProvider;
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;
    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;
    /**
     * @var CheckoutConfigProvider
     */
    private $checkoutConfigProvider;
    /**
     * @var PaymentMeansEnricher
     */
    private $paymentMeansEnricher;

    /**
     * @inheritDoc
     */
    public function initController($request, $response): void
    {
        if ('handleAdditionalData' === $request->getActionName()) {
            $this->Front()->Plugins()->JsonRequest()->setParseInput();
        }

        parent::initController($request, $response);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->errorMessageProvider = $this->get(ErrorMessageProvider::class);
        $this->snippets = $this->get('snippets');
        $this->session = $this->get('session');
        $this->checkoutConfigProvider = $this->get(CheckoutConfigProvider::class);
        $this->paymentMeansEnricher = $this->get(PaymentMeansEnricher::class);
    }

    /**
     * Main entry point for checkout processing of adyen payment
     *
     * @return void
     *
     * @throws Exception
     */
    public function indexAction(): void
    {
        $paymentMeanName = $this->getPaymentShortName();
        if (!$paymentMeanName) {
            $this->setupRedirectResponse(Url::getFrontUrl('checkout', 'shippingPayment'));

            return;
        }

        if (!Plugin::isAdyenPaymentMean($paymentMeanName)) {
            $this->errorMessageProvider->add(
                $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                    'payment/adyen/unrecognized_payment_method',
                    'Unrecognized payment method. Please select valid payment method from the list.'
                )
            );

            $this->setupRedirectResponse(Url::getFrontUrl('checkout', 'shippingPayment'));

            return;
        }

        $basketSignature = $this->persistBasket();
        $orderReference = $this->generateOrderReference($basketSignature);
        $paymentMethodType = Plugin::getAdyenPaymentType($paymentMeanName);
        if ($paymentMeanName === AdyenPayment::STORED_PAYMENT_UMBRELLA_NAME) {
            $paymentMean = $this->paymentMeansEnricher->enrichPaymentMean(
                $this->getUser()['additional']['payment'],
                (string)$this->session->get('adyenStoredPaymentMethodId')
            );

            $paymentMethodType = !empty($paymentMean['adyenPaymentType']) ? $paymentMean['adyenPaymentType'] : $paymentMethodType;
        }

        $response = CheckoutAPI::get()
            ->paymentRequest(Shop::getShopId())
            ->startTransaction(
                new StartTransactionRequest(
                    $paymentMethodType,
                    Amount::fromFloat(
                        $this->getAmount(),
                        Currency::fromIsoCode($this->getBasket()['sCurrencyName'] ?? 'EUR')
                    ),
                    $orderReference,
                    Url::getFrontUrl(
                        'AdyenPaymentProcess',
                        'handleRedirect',
                        ['signature' => $basketSignature, 'reference' => $orderReference]
                    ),
                    (array)json_decode($this->session->offsetGet('adyenPaymentMethodStateData'), true),
                    [
                        'user' => $this->getUser(),
                        'basket' => $this->getBasket(),
                    ],
                    $this->getShopperReference()
                )
            );

        if (!$response->isSuccessful()) {
            $this->errorMessageProvider->add(
                $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                    'payment/adyen/payment_processing_error',
                    'Your payment could not be processed, please resubmit order.'
                )
            );

            $this->setupRedirectResponse(Url::getFrontUrl('checkout', 'shippingPayment'));

            return;
        }

        if (!$response->isAdditionalActionRequired()) {
            $this->saveOrder(
                $response->getPspReference(),
                $orderReference
            );

            $this->setupRedirectResponse(Url::getFrontUrl('checkout', 'finish', ['sUniqueID' => $orderReference]));

            return;
        }


        if ($this->isAjaxRequest()) {
            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
            $this->Response()->setHeader('Content-Type', 'application/json');

            $this->Response()->setBody(
                json_encode([
                    'action' => $response->getAction(),
                    'signature' => $basketSignature,
                    'reference' => $orderReference,
                ])
            );

            return;
        }

        if ($response->shouldPresentToShopper() || $response->isRecieved() || $response->isPending()) {
            $this->saveOrder(
                $response->getPspReference() ?? $orderReference,
                $orderReference
            );

            Shopware()->Session()->offsetSet('adyenAction', json_encode($response->getAction()));

            $this->redirect(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $orderReference]);

            return;
        }

        $this->view->assign('action', $response->getAction());
        $this->view->assign('basketSignature', $basketSignature);
        $this->view->assign('orderReference', $orderReference);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function handleAdditionalDataAction(): void
    {
        $this->setupRedirectResponse(
            $this->handleAdditionalDataAndGetRedirectUrl($this->Request()->getParams())
        );
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function handleRedirectAction(): void
    {
        Logger::logDebug(
            'Received handleRedirectAction request',
            'Integration',
            ['request' => $this->Request()->getParams()]
        );

        $this->setupRedirectResponse(
            $this->handleAdditionalDataAndGetRedirectUrl($this->Request()->getParams())
        );
    }

    /**
     * Gets the checkout configuration for Adyen checkout instance
     *
     * @return void
     *
     * @throws Enlight_Exception
     * @throws InvalidCurrencyCode
     */
    public function getCheckoutConfigAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $amount = null;
        $amountFromBasket = $this->getAmount();
        if (!empty($amountFromBasket)) {
            $currency = Currency::fromIsoCode($this->getBasket()['sCurrencyName'] ?? 'EUR');
            $amount = Amount::fromFloat($amountFromBasket, $currency);
        }

        $this->Response()->setBody(
            json_encode($this->checkoutConfigProvider->getCheckoutConfig($amount)->toArray())
        );
    }

    /**
     * @return void
     *
     * @throws Enlight_Exception
     * @throws ConnectionSettingsNotFountException
     */
    public function disableCardDetailsAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $recurringToken = $this->Request()->get('recurringToken') ?? '';

        if ($recurringToken === '' || !$this->Request()->isPost()) {
            $this->Response()->setBody(
                json_encode(
                    [
                        'message' => $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                            'payment/adyen/disable_action_error',
                            'Disable action could not be processed, invalid request.'
                        )
                    ]
                )
            );
            $this->Response()->setHttpResponseCode(400);

            return;
        }

        $user = Shopware()->Session()->get('sUserId');

        if (empty($user)) {
            $this->Response()->setBody(
                json_encode(
                    [
                        'message' => $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                            'payment/adyen/user_not_found',
                            'Disable action could not be processed, user not found.'
                        )
                    ]
                )
            );
            $this->Response()->setHttpResponseCode(400);

            return;
        }

        $shop = Shopware()->Shop();
        $disableRequest = new DisableStoredDetailsRequest(
            $shop->getHost() . '_' . $shop->getId() . '_' . $user,
            $recurringToken
        );

        $result = CheckoutAPI::get()->checkoutConfig(Shop::getShopId())->disableStoredDetails($disableRequest);

        if (!$result->isSuccessful()) {
            $this->Response()->setBody(
                json_encode(
                    $result->toArray()
                )
            );
            $this->Response()->setHttpResponseCode(400);

            return;
        }

        $this->Response()->setBody(json_encode(['success' => true]));
    }

    /**
     * @param array $additionalData
     *
     * @return string
     *
     * @throws Exception
     */
    private function handleAdditionalDataAndGetRedirectUrl(array $additionalData): string
    {
        $basketSignature = $this->Request()->get('signature');

        if ($basketSignature) {
            try {
                $basket = $this->loadBasketFromSignature($basketSignature);
                if (!empty($this->get('session')->get('sUserId'))) {
                    $this->verifyBasketSignature($basketSignature, $basket);
                }
            } catch (Exception $e) {
                $this->errorMessageProvider->add(
                    $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                        'payment/adyen/payment_processing_error',
                        'Your payment coule not be processed, please resubmit order.'
                    )
                );

                return Url::getFrontUrl('checkout', 'shippingPayment');
            }
        }

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

            /** @var Shopware_Controllers_Frontend_Checkout $checkoutController */
            $checkoutController = Enlight_Class::Instance(Shopware_Controllers_Frontend_Checkout::class, [$this->request, $this->response]);
            $checkoutController->init();
            $checkoutController->setView($this->View());
            $checkoutController->setContainer($this->container);
            $checkoutController->setFront($this->front);
            $checkoutController->setRequest($this->request);
            $checkoutController->setResponse($this->response);
            $checkoutController->preDispatch();
            $checkoutController->confirmAction();
        }

        $response = CheckoutAPI::get()
            ->paymentRequest(Shop::getShopId())
            ->updatePaymentDetails(array_key_exists('details', $additionalData) ? $additionalData : ['details' => $additionalData]);

        if (!$response->isSuccessful()) {
            $this->errorMessageProvider->add(
                $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                    'payment/adyen/payment_processing_error',
                    'Your payment could not be processed, please resubmit order.'
                )
            );

            return Url::getFrontUrl('checkout', 'shippingPayment');
        }

        $orderReference = $this->Request()->get('reference');

        $this->saveOrder(
            $response->getPspReference(),
            $orderReference
        );

        return Url::getFrontUrl('checkout', 'finish', ['sUniqueID' => $orderReference]);
    }

    /**
     * @param string $redirectUrl
     *
     * @return void
     *
     * @throws Enlight_Exception
     * @throws Exception
     */
    private function setupRedirectResponse(string $redirectUrl)
    {
        if ($this->isAjaxRequest()) {
            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
            $this->Response()->setHeader('Content-Type', 'application/json');

            $this->Response()->setBody(json_encode(['nextStepUrl' => $redirectUrl]));

            return;
        }

        $this->redirect($redirectUrl);
    }

    /**
     * @return bool
     */
    private function isAjaxRequest(): bool
    {
        return $this->Request()->getParam('isXHR') || $this->session->offsetGet('adyenIsXHR');
    }

    /**
     * @param string $basketSignature
     *
     * @return string
     */
    private function generateOrderReference(string $basketSignature): string
    {
        /**
         * We do not want more entropy here because basket signature is actually hash string generated by Shopware and
         * we need to be max 50 characters long for Adyen validation to pass (Zip payment has 50 characters limit).
         *
         * @noinspection NonSecureUniqidUsageInspection
         */
        return md5(uniqid("{$basketSignature}_"));
    }

    /**
     * @return ShopperReference|null
     */
    private function getShopperReference(): ?ShopperReference
    {
        $shop = Shopware()->Shop();
        $user = Shopware()->Session()->get('sUserId');

        if (empty($user) || empty($shop)) {
            return null;
        }

        return ShopperReference::parse($shop->getHost() . '_' . Shop::getShopId() . '_' . $user);
    }
}
