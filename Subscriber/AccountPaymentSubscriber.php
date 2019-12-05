<?php

namespace MeteorAdyen\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use MeteorAdyen\Components\PaymentMethodService as ShopwarePaymentMethodService;
use Shopware_Controllers_Frontend_Account;

/**
 * Class AccountPaymentSubscriber
 * @package MeteorAdyen\Subscriber
 */
class AccountPaymentSubscriber implements SubscriberInterface
{
    /**
     * @var ShopwarePaymentMethodService
     */
    private $shopwarePaymentMethodService;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * AccountPaymentSubscriber constructor.
     * @param ShopwarePaymentMethodService $shopwarePaymentMethodService
     * @param Enlight_Components_Session_Namespace $session
     */
    public function __construct(
        ShopwarePaymentMethodService $shopwarePaymentMethodService,
        Enlight_Components_Session_Namespace $session
    ) {
        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
        $this->session = $session;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Frontend_Account' => 'beforeAccount',
            'Enlight_Controller_Action_PostDispatch_Frontend_Account' => 'afterAccount'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function beforeAccount(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Frontend_Account $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['savePayment'])) {
            return;
        }

        $payment = $subject->Request()->getPost('register')['payment'];
        if (!$payment || !is_string($payment)) {
            return;
        }

        if ($this->shopwarePaymentMethodService->isAdyenMethod($payment)) {
            $paymentId = $this->shopwarePaymentMethodService->getAdyenPaymentId();
            $adyenPayment = substr($payment, 6);

            $subject->Request()->setPost('register', ['payment' => $paymentId]);
            $subject->Request()->setPost('adyenPayment', $adyenPayment);
            $this->session->offsetSet('adyenPayment', $adyenPayment);
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function afterAccount(\Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Account $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['payment'])) {
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
}
