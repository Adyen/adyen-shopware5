<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Adyen\PaymentMethodService;
use MeteorAdyen\Components\Configuration;
use MeteorAdyen\Components\PaymentMethodService as ShopwarePaymentMethodService;
use MeteorAdyen\Components\UserPaymentService;
use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\Customer;
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
     * CheckoutSubscriber constructor.
     * @param Configuration $configuration
     * @param PaymentMethodService $paymentMethodService
     * @param ShopwarePaymentMethodService $shopwarePaymentMethodService
     * @param Enlight_Components_Session_Namespace $session
     * @param ModelManager $modelManager
     */
    public function __construct(
        Configuration $configuration,
        PaymentMethodService $paymentMethodService,
        ShopwarePaymentMethodService $shopwarePaymentMethodService,
        Enlight_Components_Session_Namespace $session,
        ModelManager $modelManager
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
        $this->session = $session;
        $this->modelManager = $modelManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'CheckoutFrontendPreDispatch',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'CheckoutFrontendPostDispatch',
            'sAdmin::sUpdatePayment::after' => 'sAdminSUpdatePayment',
        ];
    }

    public function sAdminSUpdatePayment(\Enlight_Hook_HookArgs $args)
    {
        $paymentId = $args->get('paymentId');
        if ($paymentId !== $this->shopwarePaymentMethodService->getAdyenPaymentId()) {
            return;
        }

        $userId = $this->session->offsetGet('sUserId');
        if (empty($userId)) {
            return false;
        }

        $qb = $this->modelManager->getDBALQueryBuilder();
        $qb->update('s_user_attributes', 'a')
            ->set('a.meteor_adyen_payment_method', ':payment')
            ->where('a.userId = :customerId')
            ->setParameter('payment', $this->session->offsetGet('adyenPayment'))
            ->setParameter('customerId', $userId)
            ->execute();
    }

    public function CheckoutFrontendPreDispatch(Enlight_Event_EventArgs $args)
    {
        $this->rewritePostPayment($args);
    }

    public function CheckoutFrontendPostDispatch(Enlight_Event_EventArgs $args)
    {
        $this->rewritePaymentData($args);
        $this->addAdyenConfig($args);

        $this->rewriteConfirmPaymentInfo($args);
        $this->rewriteFinishPaymentInfo($args);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @throws AdyenException
     */
    private function addAdyenConfig(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['shippingPayment'])) {
            return;
        }

        $adyenConfig = [
            "originKey" => $this->configuration->getOriginKey(),
            "environment" => $this->configuration->getEnvironment(),
            "paymentMethods" => json_encode($this->paymentMethodService->getPaymentMethods()),
            "paymentMethodPrefix" => $this->configuration->getPaymentMethodPrefix(),
        ];

        $subject->View()->assign('sAdyenConfig', $adyenConfig);
    }

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
        if ($formData['payment'] != $this->shopwarePaymentMethodService->getAdyenPaymentId()) {
            return;
        }
        $formData['payment'] = $this->getSelectedAdyenMethod();
        $subject->View()->assign('sFormData', $formData);
    }

    private function rewriteConfirmPaymentInfo(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        if (!$userData['additional'] ||
            !$userData['additional']['payment'] ||
            $userData['additional']['payment']['name'] != MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD) {
            return;
        }

        $selectedAdyen = $this->getSelectedAdyenMethod(false);
        $adyenMethodName = $this->getAdyenPaymentDescriptionByType($selectedAdyen);

        if (!$adyenMethodName || empty($adyenMethodName)) {
            return;
        }

        $userData['additional']['payment']['description'] = $adyenMethodName;
        $subject->View()->assign('sUserData', $userData);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @throws AdyenException
     */
    private function rewriteFinishPaymentInfo(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['finish'])) {
            return;
        }

        $sPayment = $subject->View()->getAssign('sPayment');
        if ($sPayment['name'] != MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD) {
            return;
        }

        $selectedAdyen = $this->getSelectedAdyenMethod(false);
        $adyenMethodName = $this->getAdyenPaymentDescriptionByType($selectedAdyen);

        if (!$adyenMethodName || empty($adyenMethodName)) {
            return;
        }

        $sPayment['description'] = $adyenMethodName;
        $subject->View()->assign('sPayment', $sPayment);
    }

    /**
     * @param $type
     * @return mixed
     * @throws AdyenException
     */
    private function getAdyenPaymentDescriptionByType($type)
    {
        $adyenMethods = $this->paymentMethodService->getPaymentMethods();
        $adyenMethod = null;

        foreach ($adyenMethods['paymentMethods'] as $paymentMethod) {
            if ($paymentMethod['type'] === $type) {
                return $paymentMethod['name'];
            }
        }
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
        if ($this->isAdyenMethod($payment)) {
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

    private function isAdyenMethod($payment)
    {
        return substr($payment, 0, 6) === 'adyen_';
    }

    /**
     * @param bool $prependAdyen
     * @return string
     */
    private function getSelectedAdyenMethod($prependAdyen = true)
    {
        $userId = $this->session->offsetGet('sUserId');
        if (empty($userId)) {
            return 'false';
        }
        return $this->shopwarePaymentMethodService->getSelectedAdyenMethod((int) $userId, $prependAdyen);
    }
}