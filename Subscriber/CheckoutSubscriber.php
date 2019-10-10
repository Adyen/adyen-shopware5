<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Adyen\PaymentMethodService;
use MeteorAdyen\Components\Configuration;
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
     * CheckoutSubscriber constructor.
     * @param Configuration $configuration
     * @param PaymentMethodService $paymentMethodService
     * @param Enlight_Components_Session_Namespace $session
     * @param ModelManager $modelManager
     */
    public function __construct(
        Configuration $configuration,
        PaymentMethodService $paymentMethodService,
        Enlight_Components_Session_Namespace $session,
        ModelManager $modelManager
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
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
            'sAdmin::sUpdatePayment::after' => 'sAdminSUpdatePayment'
        ];
    }

    public function sAdminSUpdatePayment(\Enlight_Hook_HookArgs $args)
    {
        $paymentId = $args->get('paymentId');
        if ($paymentId !== $this->getAdyenPaymentId()) {
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
        if ($formData['payment'] != $this->getAdyenPaymentId()) {
            return;
        }
        $formData['payment'] = $this->getSelectedAdyenMethod();
        $subject->View()->assign('sFormData', $formData);
    }

    private function rewritePostPayment(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['saveShippingPayment'])) {
            return;
        }

        $payment = $subject->Request()->get('payment');
        if (substr($payment, 0, 6) === 'adyen_') {
            $paymentId = $this->getAdyenPaymentId();
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

    private function getAdyenPaymentId()
    {
        $q = $this->modelManager->getDBALQueryBuilder()
            ->select(['id'])
            ->from('s_core_paymentmeans', 'p')
            ->where('name = :name')
            ->setParameter('name', MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD)
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();
        return (int)$q;
    }

    private function getSelectedAdyenMethod()
    {
        $userId = $this->session->offsetGet('sUserId');
        if (empty($userId)) {
            return 'false';
        }

        $qb = $this->modelManager->getDBALQueryBuilder();
        $qb->select('a.meteor_adyen_payment_method')
            ->from('s_user_attributes', 'a')
            ->where('a.userId = :customerId')
            ->setParameter('customerId', $userId);
        return 'adyen_' . $qb->execute()->fetchColumn();
    }
}