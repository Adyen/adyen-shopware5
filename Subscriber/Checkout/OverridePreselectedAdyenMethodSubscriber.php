<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

final class OverridePreselectedAdyenMethodSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(Enlight_Event_EventArgs $args): void
    {
        $subject = $args->getSubject();

        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        $preselectedPayment = Shopware()->Session()->get('sOrderVariables')['sPayment'] ?? false;
        if (false === $preselectedPayment) {
            return;
        }

        $preselectedPaymentId = (int) ($preselectedPayment['id'] ?? 0);
        $isAdyenPayment = $preselectedPayment['attribute']['adyen_type'] ?? false;
        if (0 === $preselectedPaymentId || false === $isAdyenPayment) {
            return;
        }

        $userPaymentPreset = (int) ($subject->View()->getAssign('sUserData')['additional']['user']['paymentpreset'] ?? 0);
        if (0 === $userPaymentPreset) {
            $paymentMethodId = Shopware()->Config()->get('defaultPayment');
            if (0 !== $paymentMethodId) {
                $this->overridePaymentMethod($subject, $paymentMethodId);
            }
            $subject->forward('shippingPayment', 'checkout');
            
            return;
        }

        if ($userPaymentPreset === $preselectedPaymentId) {
            return;
        }

        $this->overridePaymentMethod($subject, $preselectedPaymentId);
        $subject->forward('shippingPayment', 'checkout');
    }

    private function overridePaymentMethod($subject, $paymentMethodId)
    {
        $paymentMethod = Shopware()->Modules()->Admin()->sGetPaymentMeanById($paymentMethodId);
        if ($paymentMethod && Shopware()->Modules()->Admin()->sUpdatePayment($paymentMethodId)) {
            $userData = $subject->View()->getAssign('sUserData');
            $userData['additional']['payment'] = $paymentMethod;
            $subject->View()->assign('sUserData', $userData);
            $subject->View()->assign('sPayment', $paymentMethod);
            $subject->View()->clearAssign('adyenPaymentState');
        }
    }
}
