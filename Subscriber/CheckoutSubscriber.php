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
        Shopware_Components_Snippet_Manager $snippets,
        DataConversion $dataConversion,
        PaymentMethodsEnricherServiceInterface $paymentMethodsEnricherService
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
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

        $this->checkBasketAmount($subject);
        $this->checkFirstCheckoutStep($subject);
        $this->addAdyenConfigOnShipping($subject);
    }

    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     * @throws \Exception
     */
    private function checkBasketAmount(Shopware_Controllers_Frontend_Checkout $subject)
    {
        $userData = $subject->View()->getAssign('sUserData');

        $source = (int) ($userData['additional']['payment']['source'] ?? null);
        if (!SourceType::load($source)->equals(SourceType::adyen())) {
            return;
        }

        $basket = $subject->View()->sBasket;
        if (!$basket) {
            return;
        }
        $value = $basket['sAmount'];
        if (empty($value)) {
            $this->revertToDefaultPaymentMethod($subject);
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

    /**
     * @param Shopware_Controllers_Frontend_Checkout $subject
     * @return bool
     * @throws AdyenException
     */
    private function shouldRedirectToStep2(Shopware_Controllers_Frontend_Checkout $subject): bool
    {
        $userData = $subject->View()->getAssign('sUserData');
        $source = (int) ($userData['additional']['payment']['source'] ?? null);
        if (SourceType::load($source)->equals(SourceType::adyen())) {
            return false;
        }

        $countryCode = Shopware()->Session()->sOrderVariables['sUserData']['additional']['country']['countryiso'];
        $currency = Shopware()->Session()->sOrderVariables['sBasket']['sCurrencyName'];
        $value = Shopware()->Session()->sOrderVariables['sBasket']['AmountNumeric'];

        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
            $this->paymentMethodService->getPaymentMethods($countryCode, $currency, $value)
        );

        $selectedId = $userData['additional']['payment']['id'] ?? null;
        $paymentMethod = $adyenPaymentMethods->fetchByTypeOrId($selectedId);
        if (!$paymentMethod) {
            return true;
        }

        if (!$paymentMethod->getValue('details') && !$paymentMethod->isStoredPayment()) {
            $subject->View()->assign('adyenPaymentState', $paymentMethod->serializeMinimalState());

            return false;
        }

        return true;
    }


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
            $subject->View()->clearAssign('adyenPaymentState');
        }
    }
}
