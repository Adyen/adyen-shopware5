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
use AdyenPayment\Components\Checkout\CheckoutSnapshotRepository;
use AdyenPayment\Components\Checkout\OrderCreationLock;
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
     * Results after which no authorisation can follow. Error is deliberately absent: it is technical and
     * indeterminate, and a payment behind it may still be authorised.
     */
    private const FINAL_NEGATIVE_RESULT_CODES = ['Refused', 'Cancelled'];

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
     * @var CheckoutSnapshotRepository
     */
    private $checkoutSnapshotRepository;
    /**
     * @var OrderCreationLock
     */
    private $orderCreationLock;

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
        $this->checkoutSnapshotRepository = $this->get(CheckoutSnapshotRepository::class);
        $this->orderCreationLock = $this->get(OrderCreationLock::class);
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

        $basketSignature = $this->generateBasketSignature();
        $orderReference = $this->generateOrderReference($basketSignature);

        if (!$this->storeCheckoutSnapshot($orderReference, $basketSignature)) {
            // Without the checkout recorded, neither the returning browser nor the webhook could build an order.
            $this->errorMessageProvider->add(
                $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                    'payment/adyen/payment_processing_error',
                    'Your payment could not be processed, please resubmit order.'
                )
            );

            $this->setupRedirectResponse(Url::getFrontUrl('checkout', 'shippingPayment'));

            return;
        }
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
            $this->releaseSnapshotOnFinalResult($orderReference, $response->toArray());

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
            $this->saveOrderOnce($orderReference, $response->getPspReference());

            $this->setupRedirectResponse(Url::getFrontUrl('checkout', 'finish', ['sUniqueID' => $orderReference]));

            return;
        }

        if ($this->isAjaxRequest() && $paymentMethodType === 'scheme') {
            $this->session->offsetSet('adyen_action', $response->getAction());
            $this->session->offsetSet('signature', $basketSignature);
            $this->session->offsetSet('orderReference', $orderReference);

            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
            $this->Response()->setHeader('Content-Type', 'application/json');

            $this->Response()->setBody(json_encode([
                'nextStepUrl' =>  Url::getFrontUrl('AdyenClickToPay', 'index')
            ]));

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
                    'pspReference' => $response->getPspReference()
                ])
            );

            return;
        }

        if ($response->shouldPresentToShopper() || $response->isRecieved()) {
            $this->saveOrderOnce($orderReference, $response->getPspReference());

            Shopware()->Session()->offsetSet('adyenAction', json_encode($response->getAction()));

            $this->redirect(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $orderReference]);

            return;
        }

        $this->view->assign('action', $response->getAction());
        $this->view->assign('basketSignature', $basketSignature);
        $this->view->assign('orderReference', $orderReference);
        $this->view->assign('displayCancelButton', $response->isPending());
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
        $basketSignature = (string)$this->Request()->get('signature');
        $orderReference = (string)$this->Request()->get('reference');

        if ($orderReference) {
            try {
                $basket = $this->restoreCheckoutSnapshot($orderReference, $basketSignature);
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
        } elseif (!empty($this->Request()->getParam('adyenEmail'))) {
            $customerService->initializeCustomer($this->Request());
        }

        $response = CheckoutAPI::get()
            ->paymentRequest(Shop::getShopId())
            ->updatePaymentDetails(array_key_exists('details', $additionalData) ? $additionalData : ['details' => $additionalData]);

        if (!$response->isSuccessful()) {
            $this->releaseSnapshotOnFinalResult($orderReference, $response->toArray());

            $this->errorMessageProvider->add(
                $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                    'payment/adyen/payment_processing_error',
                    'Your payment could not be processed, please resubmit order.'
                )
            );

            return Url::getFrontUrl('checkout', 'shippingPayment');
        }

        $this->saveOrderOnce($orderReference, $response->getPspReference());

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
     * Keeps a copy of the checkout state under the Adyen order reference. When the shopper never returns from
     * the payment method, the AUTHORISATION webhook creates the order from this copy.
     *
     * @param string $orderReference
     * @param string $basketSignature
     *
     * @return bool False when the snapshot could not be written, in which case the payment must not start.
     */
    private function storeCheckoutSnapshot(string $orderReference, string $basketSignature): bool
    {
        $orderVariables = $this->session->offsetGet('sOrderVariables');

        if (!$orderVariables instanceof ArrayObject || $basketSignature === '') {
            return false;
        }

        // The same values saveOrder() reads outside sOrderVariables, plus the shop the shopper is checking out in:
        // s_user.language holds the shop they registered in, which Shopware does not update on login.
        $orderInputs = [
            CheckoutSnapshotRepository::INPUT_SHOP_ID => (int)Shopware()->Shop()->getId(),
            CheckoutSnapshotRepository::INPUT_COMMENT => (string)$this->session->offsetGet('sComment'),
            CheckoutSnapshotRepository::INPUT_DISPATCH_ID => (int)$this->session->offsetGet('sDispatch'),
            CheckoutSnapshotRepository::INPUT_DEVICE_TYPE => (string)$this->Request()->getDeviceType(),
        ];

        try {
            $this->checkoutSnapshotRepository->store(
                $orderReference,
                $orderVariables->getArrayCopy(),
                $basketSignature,
                $orderInputs
            );
        } catch (Exception $exception) {
            Logger::logError(
                'Storing the checkout snapshot for order reference ' . $orderReference . ' failed: ' .
                $exception->getMessage(),
                'Integration'
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $orderReference
     * @param array $responseData
     *
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    private function releaseSnapshotOnFinalResult(string $orderReference, array $responseData): void
    {
        $resultCode = (string)($responseData['resultCode'] ?? '');

        if (in_array($resultCode, self::FINAL_NEGATIVE_RESULT_CODES, true)) {
            $this->checkoutSnapshotRepository->release($orderReference);
        }
    }

    /**
     * Generates the basket signature without persisting a row for it. The signature is still handed to the
     * shopper and verified on the way back, but the checkout is stored under the order reference instead, so a
     * single row covers one payment attempt.
     *
     * @return string
     * @throws Exception
     */
    private function generateBasketSignature(): string
    {
        $orderVariables = $this->session->offsetGet('sOrderVariables');
        $orderVariables = $orderVariables instanceof ArrayObject ? $orderVariables->getArrayCopy() : [];

        return $this->get('basket_signature_generator')->generateSignature(
            $orderVariables['sBasket'] ?? [],
            $this->session->offsetGet('sUserId')
        );
    }

    /**
     * Puts the stored checkout back into the session so the rest of the flow behaves as it does for a shopper
     * who never left.
     *
     * @param string $orderReference
     * @param string $basketSignature
     *
     * @return ArrayObject
     *
     * @throws RuntimeException|\Doctrine\DBAL\Exception
     */
    private function restoreCheckoutSnapshot(string $orderReference, string $basketSignature): ArrayObject
    {
        $snapshot = $this->checkoutSnapshotRepository->load($orderReference);

        if (!$snapshot) {
            // Payments started before the snapshot was introduced persisted the basket under the signature only.
            // Shopware's loader throws when that row is missing too.
            return $this->loadBasketFromSignature($basketSignature);
        }

        // The reference travels in URLs and in the order record, so it is not a secret and cannot be the only
        // thing standing between a request and somebody else's checkout. Nothing is put into the session until
        // the request has also presented the signature this checkout was stored with.
        if (!hash_equals(CheckoutSnapshotRepository::signatureOf($snapshot), $basketSignature)) {
            throw new RuntimeException(
                sprintf('Checkout snapshot for reference %s does not match the presented signature', $orderReference)
            );
        }

        $basket = new ArrayObject(
            CheckoutSnapshotRepository::orderVariablesOf($snapshot),
            ArrayObject::ARRAY_AS_PROPS
        );

        $this->session->offsetSet('sOrderVariables', $basket);

        return $basket;
    }

    /**
     * Creates the order unless it is already there, and gives up the snapshot afterwards. The lock keeps this
     * out of step with the webhook, which runs the same check against the same reference from its own
     * connection, so the two can never both decide the order is missing.
     *
     * @param string $orderReference
     * @param string|null $pspReference
     *
     * @return void
     *
     * @throws Exception
     */
    private function saveOrderOnce(string $orderReference, ?string $pspReference): void
    {
        $this->orderCreationLock->synchronized($orderReference, function () use ($orderReference, $pspReference) {
            // saveOrder() returns false without creating anything when the transaction id is empty, and the
            // PSP reference is nullable on both responses.
            if ($this->orderCreationLock->orderExists($orderReference)
                || $this->saveOrder($pspReference ?: $orderReference, $orderReference)
            ) {
                $this->checkoutSnapshotRepository->release($orderReference);
            }
        });
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
