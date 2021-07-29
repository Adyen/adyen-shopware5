<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\PaymentMethodsEnricherServiceInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\DataConversion;
use AdyenPayment\AdyenPayment;
use sAdmin;
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

    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;

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
        Enlight_Components_Session_Namespace $session,
        Shopware_Components_Snippet_Manager $snippets,
        DataConversion $dataConversion,
        PaymentMethodsEnricherServiceInterface $paymentMethodsEnricherService,
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
        $this->session = $session;
        $this->snippets = $snippets;
        $this->dataConversion = $dataConversion;
        $this->paymentMethodsEnricherService = $paymentMethodsEnricherService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'checkoutFrontendPostDispatch',
        ];
    }


    /**
     * @param Enlight_Event_EventArgs $args
     * @throws AdyenException
     */
    public function checkoutFrontendPostDispatch(Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();

        $this->checkBasketAmount($subject); // TODO probably needs to stay, depends on investigation
        $this->checkFirstCheckoutStep($subject); // TODO needs to stay, as redirectToStep2 stays
        $this->addAdyenConfigOnShipping($subject); // TODO needs to stay

        if (in_array($subject->Request()->getActionName(), ['shippingPayment', 'saveShippingPayment'])) {
            $this->addPaymentSnippets($subject);
        }

        if (in_array($subject->Request()->getActionName(), ['confirm'])) {
            $this->addConfirmSnippets($subject);
        }
    }

    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     * @throws \Exception
     */
    private function checkBasketAmount(Shopware_Controllers_Frontend_Checkout $subject)
    {
        $userData = $subject->View()->getAssign('sUserData');

        if (!$userData['additional'] ||
            !$userData['additional']['payment'] ||
            (int) $userData['additional']['payment']['source'] !== SourceType::adyen()->getType()) {
            return;
        }

        $basket = $subject->View()->sBasket;
        if (!$basket) {
            return;
        }
        $value = $basket['sAmount'];
        if (empty($value)) {
            $this->revertToDefaultPaymentMethod($subject); // TODO probably needs to stay
        }
    }


    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     * @throws AdyenException
     */
    private function addAdyenConfigOnShipping(Shopware_Controllers_Frontend_Checkout $subject)
    {
        if (!in_array($subject->Request()->getActionName(), ['shippingPayment', 'confirm'])) {
            return;
        }

        $this->admin = Shopware()->Modules()->Admin();
        $enrichedPaymentMethods = $this->paymentMethodsEnricherService->__invoke(
            $this->admin->sGetPaymentMeans()
        );

        $shop = Shopware()->Shop();

        $adyenConfig = [
            "shopLocale" => $this->dataConversion->getISO3166FromLocale($shop->getLocale()->getLocale()),
            "clientKey" => $this->configuration->getClientKey($shop),
            "environment" => $this->configuration->getEnvironment($shop),
            "enrichedPaymentMethods" => $enrichedPaymentMethods,
        ];

        $view = $subject->View();
        $view->assign('sAdyenConfig', $adyenConfig);
    }

    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     */
    private function addConfirmSnippets(Shopware_Controllers_Frontend_Checkout $subject)
    {
        $errorSnippets = $this->snippets->getNamespace('adyen/checkout/error');

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
        $snippets['errorTransactionNoSession'] = $errorSnippets->get(
            'errorTransactionNoSession',
            'Your transaction was cancelled due to an unknown reason. Please make sure your browser allows cookies.',
            true
        );

        $subject->View()->assign('mAdyenSnippets', htmlentities(json_encode($snippets)));
    }

    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     */
    private function addPaymentSnippets(Shopware_Controllers_Frontend_Checkout $subject)
    {
        $paymentSnippets = $this->snippets->getNamespace('adyen/checkout/payment');

        $snippets = [
            'updatePaymentInformation' => $paymentSnippets->get(
                'updatePaymentInformation',
                'Update your payment information',
                true
            ),
            'storedPaymentMethodTitle' => $paymentSnippets->get(
                'storedPaymentMethodTitle',
                'Stored payment methods',
                true
            ),
            'paymentMethodTitle' => $paymentSnippets->get(
                'paymentMethodTitle',
                'Payment methods',
                true
            ),
        ];

        $subject->View()->assign('mAdyenSnippets', htmlentities(json_encode($snippets)));
    }

    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     * @throws AdyenException
     */
    private function checkFirstCheckoutStep(Shopware_Controllers_Frontend_Checkout $subject)
    {
        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
            return;
        }

        if ($this->shouldRedirectToStep2($subject)) {
            $subject->forward(
                'shippingPayment',
                'checkout'
            );
        }
    }


    //TODO: investigate if we can store the details (raw data),
    // so we don't need to make a new call to adyen
    // and check if we can remove session
    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     * @return bool
     * @throws AdyenException
     */
    private function shouldRedirectToStep2(Shopware_Controllers_Frontend_Checkout $subject): bool
    {
        $userData = $subject->View()->getAssign('sUserData');
        $source = (int) $userData['additional']['payment']['source'] ?? null;
        if (SourceType::load($source)->equals(SourceType::adyen())) {
            return false;
        }

        $countryCode = Shopware()->Session()->sOrderVariables['sUserData']['additional']['country']['countryiso'];
        $currency = Shopware()->Session()->sOrderVariables['sBasket']['sCurrencyName'];
        $value = Shopware()->Session()->sOrderVariables['sBasket']['AmountNumeric'];

        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
            $this->paymentMethodService->getPaymentMethods($countryCode, $currency, $value)
        );

        $selectedId = $userData['additional']['payment']['id'];
        $paymentMethod = $adyenPaymentMethods->fetchByTypeOrId($selectedId);
        if (!$paymentMethod) {
            return true;
        }

        //TODO: payment details are also used in
        if (!$paymentMethod->getValue('details') && !$paymentMethod->isStoredPayment()) {
            $subject->View()->assign('sAdyenSetSession', $paymentMethod->serializeMinimalState()); // TODO check
            // TODO would be so much more logic
//            $subject->View()->assign('adyenPaymentState', $paymentMethod->serializeMinimalState()); // TODO check

            return false;
        }

        return true;
    }


    // TODO: investigate deeper
    private function revertToDefaultPaymentMethod(Shopware_Controllers_Frontend_Checkout $subject)
    {
        $defaultPaymentId = Shopware()->Config()->get('defaultPayment');
        $defaultPayment = Shopware()->Modules()->Admin()->sGetPaymentMeanById($defaultPaymentId);
        if (Shopware()->Modules()->Admin()->sUpdatePayment($defaultPaymentId)) {
            // Replace Adyen payment method in the template with the default payment method.
            $userData = $subject->View()->getAssign('sUserData');
            $userData['additional']['payment'] = $defaultPayment;
            $subject->View()->assign('sUserData', $userData);
            $subject->View()->assign('sPayment', $defaultPayment);
            $subject->View()->clearAssign('sAdyenSetSession'); // TODO needs further investigation, as used/set in shouldRedirectToStep2
        }
    }
}
