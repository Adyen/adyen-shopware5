<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Models\Payment\PaymentMean;
use Enlight\Event\SubscriberInterface;

final class OverridePreselectedAdyenMethodSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        return;

        $subject = $args->getSubject();
        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        $preselectedPaymentMean = PaymentMean::createFromShopwareArray(
            Shopware()->Session()->get('sOrderVariables')['sPayment'] ?? []
        );

        if (!$preselectedPaymentMean->isAdyenType()) {
            return;
        }

        if ($preselectedPaymentMean->getId() === $this->provideUserPaymentPresetId($subject)) {
            return;
        }

        $overridePaymentId = $this->determineOverridePaymentId($subject, $preselectedPaymentMean);
        $this->overridePaymentMethod($subject, $overridePaymentId);
        $subject->forward('shippingPayment', 'checkout');
    }

    private function provideUserPaymentPresetId(\Enlight_Controller_Action $subject): int
    {
        return (int) ($subject->View()->getAssign('sUserData')['additional']['user']['paymentpreset'] ?? 0);
    }

    private function determineOverridePaymentId(
        \Enlight_Controller_Action $subject,
        PaymentMean $preselectedPaymentMean
    ): int {
        if (0 === $this->provideUserPaymentPresetId($subject)) {
            return (int) Shopware()->Config()->get('defaultPayment');
        }

        return $preselectedPaymentMean->getId();
    }

    private function overridePaymentMethod(\Enlight_Controller_Action $subject, int $paymentMethodId): void
    {
        $paymentMean = PaymentMean::createFromShopwareArray(
            Shopware()->Modules()->Admin()->sGetPaymentMeanById($paymentMethodId)
        );

        if (0 === $paymentMean->getId()) {
            return;
        }

        $updated = Shopware()->Modules()->Admin()->sUpdatePayment($paymentMean->getId());
        if (!$updated) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        $userData['additional']['payment'] = $paymentMean->getRaw();
        $subject->View()->assign('sUserData', $userData);
        $subject->View()->assign('sPayment', $paymentMean->getRaw());
        $subject->View()->clearAssign('adyenPaymentState');
    }
}
