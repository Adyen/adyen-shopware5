<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Adyen\Util\Currency;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_Front;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Adyen\PaymentMethodService;
use MeteorAdyen\Components\Configuration;
use MeteorAdyen\Components\PaymentMethodService as ShopwarePaymentMethodService;
use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Model\ModelManager;
use Shopware_Components_Snippet_Manager;
use Shopware_Controllers_Frontend_Checkout;

/**
 * Class CheckoutSubscriber
 * @package MeteorAdyen\Subscriber
 */
class CheckoutSubscriber implements SubscriberInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var PaymentMethodService
     */
    protected $paymentMethodService;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ShopwarePaymentMethodService
     */
    private $shopwarePaymentMethodService;

    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;

    /**
     * @var \Enlight_Controller_Front
     */
    private $front;

    /**
     * CheckoutSubscriber constructor.
     * @param Configuration $configuration
     * @param PaymentMethodService $paymentMethodService
     * @param ShopwarePaymentMethodService $shopwarePaymentMethodService
     * @param Enlight_Components_Session_Namespace $session
     * @param ModelManager $modelManager
     * @param Shopware_Components_Snippet_Manager $snippets
     * @param Enlight_Controller_Front $front
     */
    public function __construct(
        Configuration $configuration,
        PaymentMethodService $paymentMethodService,
        ShopwarePaymentMethodService $shopwarePaymentMethodService,
        Enlight_Components_Session_Namespace $session,
        ModelManager $modelManager,
        Shopware_Components_Snippet_Manager $snippets,
        Enlight_Controller_Front $front
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
        $this->session = $session;
        $this->modelManager = $modelManager;
        $this->snippets = $snippets;
        $this->front = $front;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'CheckoutFrontendPreDispatch',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'CheckoutFrontendPostDispatch',
            'sAdmin::sUpdatePayment::after' => 'sAdminAfterSUpdatePayment',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function CheckoutFrontendPreDispatch(Enlight_Event_EventArgs $args)
    {
        $this->rewritePostPayment($args);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @throws AdyenException
     */
    public function CheckoutFrontendPostDispatch(Enlight_Event_EventArgs $args)
    {
        $this->rewritePaymentData($args);
        $this->addAdyenConfigOnShipping($args);
        $this->addAdyenConfigOnConfirm($args);
        $this->addAdyenSnippets($args);
        $this->addAdyenGooglePay($args);
    }

    /**
     * @param \Enlight_Hook_HookArgs $args
     * @return bool|void
     */
    public function sAdminAfterSUpdatePayment(\Enlight_Hook_HookArgs $args)
    {
        $paymentId = $args->get('paymentId');
        if (!$paymentId) {
            $paymentId = $this->front->Request()->getPost('sPayment');
        }

        if ($paymentId !== $this->shopwarePaymentMethodService->getAdyenPaymentId()) {
            return;
        }

        $userId = (int)$this->session->offsetGet('sUserId');
        if (empty($userId)) {
            return false;
        }

        $this->shopwarePaymentMethodService->setUserAdyenMethod($userId, $this->session->offsetGet('adyenPayment'));
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @throws AdyenException
     */
    private function addAdyenConfigOnShipping(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['shippingPayment'])) {
            return;
        }
        $view = $subject->View();

        $countryCode = $view->getAssign('sUserData')['additional']['country']['countryiso'];
        $currency = $view->getAssign('sBasket')['sCurrencyName'];
        $value = $view->getAssign('sBasket')['AmountNumeric'];
        $paymentMethods = $this->paymentMethodService->getPaymentMethods($countryCode, $currency, $value);

        $adyenConfig = [
            "originKey" => $this->configuration->getOriginKey(),
            "environment" => $this->configuration->getEnvironment(),
            "paymentMethods" => json_encode($paymentMethods),
            "paymentMethodPrefix" => $this->configuration->getPaymentMethodPrefix(),
            "jsComponents3DS2ChallengeImageSize" => $this->configuration->getJsComponents3DS2ChallengeImageSize(),
        ];

        $view->assign('sAdyenConfig', $adyenConfig);
    }


    /**
     * @param Enlight_Event_EventArgs $args
     */
    private function addAdyenConfigOnConfirm(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
            return;
        }

        $adyenConfig = [
            "jsComponents3DS2ChallengeImageSize" => $this->configuration->getJsComponents3DS2ChallengeImageSize(),
        ];

        $subject->View()->assign('sAdyenConfig', $adyenConfig);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    private function addAdyenSnippets(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
            return;
        }

        $errorSnippets = $this->snippets->getNamespace('meteor_adyen/checkout/error');

        $snippets = [];
        $snippets['errorTransactionCancelled'] = $errorSnippets->get(
            'errorTransactionCancelled',
            'Your transaction was cancelled by the Payment Service Provider.',
            true
        );
        $snippets['errorTransactionProcessing'] = $errorSnippets->get(
            'errorTransactionProcessing',
            'An error occured while processing your payment.',
            true
        );
        $snippets['errorTransactionRefused'] = $errorSnippets->get(
            'errorTransactionRefused',
            'Your transaction was refused by the Payment Service Provider.',
            true
        );
        $snippets['errorTransactionUnknown'] = $errorSnippets->get(
            'errorTransactionUnknown',
            'Your transaction was cancelled due to an unknown reason.',
            true
        );

        $subject->View()->assign('mAdyenSnippets', htmlentities(json_encode($snippets)));
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    private function rewritePaymentData(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['shippingPayment', 'saveShippingPayment'])) {
            return;
        }

        $formData = $subject->View()->getAssign('sFormData');
        if (!$formData['payment']) {
            return;
        }
        if ((int)$formData['payment'] !== $this->shopwarePaymentMethodService->getAdyenPaymentId()) {
            return;
        }
        $formData['payment'] = $this->shopwarePaymentMethodService->getActiveUserAdyenMethod();
        $subject->View()->assign('sFormData', $formData);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    private function rewritePostPayment(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['shippingPayment', 'saveShippingPayment'])) {
            return;
        }

        $payment = $subject->Request()->get('payment');
        if (!$payment || !is_string($payment)) {
            return;
        }

        if ($this->shopwarePaymentMethodService->isAdyenMethod($payment)) {
            $paymentId = $this->shopwarePaymentMethodService->getAdyenPaymentId();
            $adyenPayment = substr($payment, 6);

            $subject->Request()->setParams([
                'payment' => $paymentId,
                'adyenPayment' => $adyenPayment
            ]);
            $subject->Request()->setPost('payment', $paymentId);
            $subject->Request()->setPost('adyenPayment', $adyenPayment);
            $this->session->offsetSet('adyenPayment', $adyenPayment);
        }
    }

    private function addAdyenGooglePay(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        if (!$userData['additional'] ||
            !$userData['additional']['payment'] ||
            $userData['additional']['payment']['name'] !== MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD) {
            return;
        }

        $basket = $subject->View()->getAssign('sBasket');
        if (!$basket) {
            return;
        }

        if ($this->shopwarePaymentMethodService->getActiveUserAdyenMethod(false) !== 'paywithgoogle') {
            return;
        }

        $currencyUtil = new Currency();
        $adyenGoogleConfig = [
            'environment' => 'TEST',
            'showPayButton' => true,
            'currencyCode' => $basket['sCurrencyName'],
            'amount' => $currencyUtil->sanitize($basket['AmountNumeric'], $basket['sCurrencyName']),
            'configuration' => [
                'gatewayMerchantId' => $this->configuration->getMerchantAccount(),
                'merchantName' => Shopware()->Shop()->getName()
            ],
        ];
        if ($this->configuration->getEnvironment() === Configuration::ENV_LIVE) {
            $adyenGoogleConfig['environment'] = 'PRODUCTION';
            $adyenGoogleConfig['configuration']['merchantIdentifier'] = ''; // TODO: Configurable merchant identifier
        }
        $subject->View()->assign('sAdyenGoogleConfig', htmlentities(json_encode($adyenGoogleConfig)));
    }
}
