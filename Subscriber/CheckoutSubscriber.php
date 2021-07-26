<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use Adyen\Util\Currency;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\PaymentMethodsEnricherServiceInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_Front;
use Enlight_Event_EventArgs;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\DataConversion;
use AdyenPayment\Components\Manager\AdyenManager;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\AdyenPayment;
use sAdmin;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;
use Shopware\Components\Model\ModelManager;
use Shopware_Components_Snippet_Manager;
use Shopware_Controllers_Frontend_Checkout;

/**
 * Class CheckoutSubscriber
 * @package AdyenPayment\Subscriber
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
//
//    /**
//     * @var ModelManager
//     */
//    private $modelManager;
//
//    /**
//     * @var ShopwarePaymentMethodService
//     */
//    private $shopwarePaymentMethodService;
//
//    /**
//     * @var Shopware_Components_Snippet_Manager
//     */
//    private $snippets;
//
//    /**
//     * @var \Enlight_Controller_Front
//     */
//    private $front;
//
//    /**
//     * @var AdyenManager
//     */
//    private $adyenManager;
//
    /**
     * @var DataConversion
     */
    private $dataConversion;
    /**
     * @var PaymentMethodsEnricherServiceInterface
     */
    private $paymentMethodsEnricherService;

    /**
     * @var sAdmin
     */
    private $admin;

    public function __construct(
        Configuration $configuration,
        PaymentMethodService $paymentMethodService,
//        Enlight_Components_Session_Namespace $session,
//        ModelManager $modelManager,
//        ShopwarePaymentMethodService $shopwarePaymentMethodService,
//        Shopware_Components_Snippet_Manager $snippets,
//        Enlight_Controller_Front $front,
//        AdyenManager $adyenManager,
        DataConversion $dataConversion,
        PaymentMethodsEnricherServiceInterface $paymentMethodsEnricherService
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
//        $this->session = $session;
//        $this->modelManager = $modelManager;
//        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
//        $this->snippets = $snippets;
//        $this->front = $front;
//        $this->adyenManager = $adyenManager;
        $this->dataConversion = $dataConversion;
        $this->paymentMethodsEnricherService = $paymentMethodsEnricherService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'checkoutFrontendPreDispatch',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'checkoutFrontendPostDispatch',
//            'sAdmin::sUpdatePayment::after' => 'sAdminAfterSUpdatePayment',
//            'sAdmin::sGetDispatchBasket::after' => 'sAdminAfterSGetDispatchBasket',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function checkoutFrontendPreDispatch(Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();
//
//        $this->rewritePostPayment($subject);
//        $this->unsetPaymentSessions($subject);
    }


    /**
     * @param Enlight_Event_EventArgs $args
     * @throws AdyenException
     */
    public function checkoutFrontendPostDispatch(Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();

//        $this->checkBasketAmount($subject);
//        $this->checkFirstCheckoutStep($subject);
//        $this->rewritePaymentData($subject);
        $this->addAdyenConfigOnShipping($subject);
//        $this->addAdyenGooglePay($subject);
//
//        if (in_array($subject->Request()->getActionName(), ['shippingPayment', 'saveShippingPayment'])) {
//            $this->addPaymentSnippets($subject);
//        }
//
//        if (in_array($subject->Request()->getActionName(), ['confirm'])) {
//            $this->addConfirmSnippets($subject);
//        }
    }

//    TODO remove
//
//    /**
//     * @param \Enlight_Hook_HookArgs $args
//     * @return void
//     */
//    public function sAdminAfterSUpdatePayment(\Enlight_Hook_HookArgs $args)
//    {
//        $paymentId = $args->get('paymentId');
//        if (!$paymentId) {
//            $paymentId = $this->front->Request()->getPost('sPayment');
//        }
//
//        if ($paymentId !== $this->shopwarePaymentMethodService->getAdyenPaymentId()) {
//            return;
//        }
//
//        $userId = (int)$this->session->offsetGet('sUserId');
//        if (empty($userId)) {
//            return;
//        }
//
//        $adyenPayment = Shopware()->Front()->Request()->getParams()['adyenPayment'];
//        if (!$adyenPayment) {
//            return;
//        }
//
//        $this->shopwarePaymentMethodService->setUserAdyenMethod($userId, $adyenPayment);
//    }
//

//    TODO remove
//
//    /**
//     * @param \Enlight_Hook_HookArgs $args
//     * @return mixed
//     */
//    public function sAdminAfterSGetDispatchBasket(\Enlight_Hook_HookArgs $args)
//    {
//        $basket = $args->getReturn();
//
//        if ($this->shopwarePaymentMethodService->isAdyenMethod($basket['paymentID'])) {
//            $basket['paymentID'] = $this->shopwarePaymentMethodService->getAdyenPaymentId();
//        }
//
//        return $basket;
//    }
//

//    TODO remove
//
//    /**
//     * @param Shopware_Controllers_Frontend_Checkout $subject
//     * @throws \Exception
//     */
//    private function checkBasketAmount(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        $userData = $subject->View()->getAssign('sUserData');
//        if (!$userData['additional'] ||
//            !$userData['additional']['payment'] ||
//            $userData['additional']['payment']['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD) {
//            return;
//        }
//
//        $basket = $subject->View()->sBasket;
//        if (!$basket) {
//            return;
//        }
//        $value = $basket['sAmount'];
//        if (empty($value)) {
//            $this->revertToDefaultPaymentMethod($subject);
//        }
//    }
//

    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     * @throws AdyenException
     */
    private function addAdyenConfigOnShipping(Shopware_Controllers_Frontend_Checkout $subject)
    {
        if (!in_array($subject->Request()->getActionName(), ['shippingPayment', 'confirm'])) {
            return;
        }
        $view = $subject->View();

        $countryCode = $view->getAssign('sUserData')['additional']['country']['countryiso'];
        $currency = $view->getAssign('sBasket')['sCurrencyName'];
        $value = $view->getAssign('sBasket')['AmountNumeric'];

        $this->admin = Shopware()->Modules()->Admin();
        $enrichedPaymentMethods = $this->paymentMethodsEnricherService->__invoke($this->admin->sGetPaymentMeans());

        $shop = Shopware()->Shop();

        $adyenConfig = [
            "shopLocale" => $this->dataConversion->getISO3166FromLocale($shop->getLocale()->getLocale()),
            "clientKey" => $this->configuration->getClientKey($shop),
            "environment" => $this->configuration->getEnvironment($shop),
            "enrichedPaymentMethods" => $enrichedPaymentMethods,
            "paymentMethodPrefix" => $this->configuration->getPaymentMethodPrefix($shop),
        ];

        $view->assign('sAdyenConfig', $adyenConfig);
    }


//    TODO remove
//
//    /**
//     * @param Shopware_Controllers_Frontend_Checkout $subject
//     */
//    private function addConfirmSnippets(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        $errorSnippets = $this->snippets->getNamespace('adyen/checkout/error');
//
//        $snippets = [];
//        $snippets['errorTransactionCancelled'] = $errorSnippets->get(
//            'errorTransactionCancelled',
//            'Your transaction was cancelled by the Payment Service Provider.',
//            true
//        );
//        $snippets['errorTransactionProcessing'] = $errorSnippets->get(
//            'errorTransactionProcessing',
//            'An error occured while processing your payment.',
//            true
//        );
//        $snippets['errorTransactionRefused'] = $errorSnippets->get(
//            'errorTransactionRefused',
//            'Your transaction was refused by the Payment Service Provider.',
//            true
//        );
//        $snippets['errorTransactionUnknown'] = $errorSnippets->get(
//            'errorTransactionUnknown',
//            'Your transaction was cancelled due to an unknown reason.',
//            true
//        );
//        $snippets['errorTransactionNoSession'] = $errorSnippets->get(
//            'errorTransactionNoSession',
//            'Your transaction was cancelled due to an unknown reason. Please make sure your browser allows cookies.',
//            true
//        );
//
//        $subject->View()->assign('mAdyenSnippets', htmlentities(json_encode($snippets)));
//    }
//

//    TODO remove
//
//    /**
//     * @param Shopware_Controllers_Frontend_Checkout $subject
//     */
//    private function addPaymentSnippets(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        $paymentSnippets = $this->snippets->getNamespace('adyen/checkout/payment');
//
//        $snippets = [
//            'updatePaymentInformation' => $paymentSnippets->get(
//                'updatePaymentInformation',
//                'Update your payment information',
//                true
//            ),
//            'storedPaymentMethodTitle' => $paymentSnippets->get(
//                'storedPaymentMethodTitle',
//                'Stored payment methods',
//                true
//            ),
//            'paymentMethodTitle' => $paymentSnippets->get(
//                'paymentMethodTitle',
//                'Payment methods',
//                true
//            ),
//        ];
//
//        $subject->View()->assign('mAdyenSnippets', htmlentities(json_encode($snippets)));
//    }
//

//    TODO remove
//
//    /**
//     * @param Shopware_Controllers_Frontend_Checkout $subject
//     */
//    private function rewritePaymentData(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        if (!in_array($subject->Request()->getActionName(), ['shippingPayment', 'saveShippingPayment'])) {
//            return;
//        }
//
//        $formData = $subject->View()->getAssign('sFormData');
//        if (!$formData['payment']) {
//            return;
//        }
//        if ((int)$formData['payment'] !== $this->shopwarePaymentMethodService->getAdyenPaymentId()) {
//            return;
//        }
//        $formData['payment'] = $this->shopwarePaymentMethodService->getActiveUserAdyenMethod();
//        $subject->View()->assign('sFormData', $formData);
//    }
//

//    TODO remove
//
//    /**
//     * @param Shopware_Controllers_Frontend_Checkout $subject
//     */
//    private function rewritePostPayment(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        if (!in_array($subject->Request()->getActionName(), ['shippingPayment', 'saveShippingPayment'])) {
//            return;
//        }
//
//        $payment = $subject->Request()->get('payment');
//        if (!$payment || !is_string($payment)) {
//            return;
//        }
//
//        $this->session->offsetSet(AdyenPayment::SESSION_ADYEN_PAYMENT_VALID, false);
//        if ($this->shopwarePaymentMethodService->isAdyenMethod($payment)) {
//            $paymentId = $this->shopwarePaymentMethodService->getAdyenPaymentId();
//            $adyenPayment = $this->shopwarePaymentMethodService->getAdyenMethod($payment);
//
//            $subject->Request()->setParams([
//                'payment' => $paymentId,
//                'adyenPayment' => $adyenPayment
//            ]);
//            $subject->Request()->setPost('payment', $paymentId);
//            $subject->Request()->setPost('adyenPayment', $adyenPayment);
//            $this->session->offsetSet(AdyenPayment::SESSION_ADYEN_PAYMENT, $adyenPayment);
//            $this->session->offsetSet(AdyenPayment::SESSION_ADYEN_PAYMENT_VALID, true);
//        }
//    }


//    TODO remove
//
//    private function addAdyenGooglePay(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
//            return;
//        }
//
//        $userData = $subject->View()->getAssign('sUserData');
//        if (!$userData['additional'] ||
//            !$userData['additional']['payment'] ||
//            $userData['additional']['payment']['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD) {
//            return;
//        }
//
//        $basket = $subject->View()->getAssign('sBasket');
//        if (!$basket) {
//            return;
//        }
//
//        if ($this->shopwarePaymentMethodService->getActiveUserAdyenMethod(false) !== 'paywithgoogle') {
//            return;
//        }
//
//        $currencyUtil = new Currency();
//        $adyenGoogleConfig = [
//            'environment' => 'TEST',
//            'showPayButton' => true,
//            'currencyCode' => $basket['sCurrencyName'],
//            'amount' => $currencyUtil->sanitize($basket['AmountNumeric'], $basket['sCurrencyName']),
//            'configuration' => [
//                'gatewayMerchantId' => $this->configuration->getMerchantAccount(),
//                'merchantName' => Shopware()->Shop()->getName()
//            ],
//        ];
//        if ($this->configuration->getEnvironment() === Configuration::ENV_LIVE) {
//            $adyenGoogleConfig['environment'] = 'PRODUCTION';
//            $adyenGoogleConfig['configuration']['merchantIdentifier'] = $this->configuration->getGoogleMerchantId();
//        }
//        $subject->View()->assign('sAdyenGoogleConfig', htmlentities(json_encode($adyenGoogleConfig)));
//    }
//

//    TODO remove
//
//    /**
//     * @param Shopware_Controllers_Frontend_Checkout $subject
//     * @throws AdyenException
//     */
//    private function checkFirstCheckoutStep(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
//            return;
//        }
//
//        if ($this->shouldRedirectToStep2($subject)) {
//            $subject->forward(
//                'shippingPayment',
//                'checkout'
//            );
//        }
//    }
//

//    TODO remove
//
//    /**
//     * @param Shopware_Controllers_Frontend_Checkout $subject
//     * @return bool
//     * @throws AdyenException
//     */
//    private function shouldRedirectToStep2(Shopware_Controllers_Frontend_Checkout $subject): bool
//    {
//        $userData = $subject->View()->getAssign('sUserData');
//        if (!$userData['additional'] ||
//            !$userData['additional']['payment'] ||
//            $userData['additional']['payment']['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD) {
//            return false;
//        }
//
//        $countryCode = Shopware()->Session()->sOrderVariables['sUserData']['additional']['country']['countryiso'];
//        $currency = Shopware()->Session()->sOrderVariables['sBasket']['sCurrencyName'];
//        $value = Shopware()->Session()->sOrderVariables['sBasket']['AmountNumeric'];
//
//        $selectedType = $userData['additional']['user'][AdyenPayment::ADYEN_PAYMENT_PAYMENT_METHOD];
//
//        if ($selectedType === null) {
//            return true;
//        }
//
//        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
//            $this->paymentMethodService->getPaymentMethods($countryCode, $currency, $value)
//        );
//
//        $paymentMethod = $adyenPaymentMethods->fetchByTypeOrId($selectedType);
//        if (!$paymentMethod) {
//            return true;
//        }
//
//        if (!$paymentMethod->getValue('details') && !$paymentMethod->isStoredPayment()) {
//            $subject->View()->assign('sAdyenSetSession', $paymentMethod->serializeMinimalState());
//
//            return false;
//        }
//
//        return !$this->session->offsetExists(AdyenPayment::SESSION_ADYEN_PAYMENT_VALID);
//    }
//

//    TODO remove
//
//    private function revertToDefaultPaymentMethod(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        $defaultPaymentId = Shopware()->Config()->get('defaultPayment');
//        $defaultPayment = Shopware()->Modules()->Admin()->sGetPaymentMeanById($defaultPaymentId);
//        if (Shopware()->Modules()->Admin()->sUpdatePayment($defaultPaymentId)) {
//            $this->adyenManager->unsetPaymentDataInSession();
//            // Replace Adyen payment method in the template with the default payment method.
//            $userData = $subject->View()->getAssign('sUserData');
//            $userData['additional']['payment'] = $defaultPayment;
//            $subject->View()->assign('sUserData', $userData);
//            $subject->View()->assign('sPayment', $defaultPayment);
//            $subject->View()->clearAssign('sAdyenSetSession');
//        }
//    }
//

//    private function unsetPaymentSessions(Shopware_Controllers_Frontend_Checkout $subject)
//    {
//        if ($subject->Request()->getActionName() !== 'finish') {
//            return;
//        }
//
//        $this->adyenManager->unsetPaymentDataInSession();
//    }
}
