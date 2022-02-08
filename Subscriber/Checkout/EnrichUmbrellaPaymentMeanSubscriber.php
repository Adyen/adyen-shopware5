<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Shopware\Provider\PaymentMeansProviderInterface;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class EnrichUmbrellaPaymentMeanSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;
    private PaymentMeansProviderInterface $paymentMeansProvider;

    public function __construct(
        Enlight_Components_Session_Namespace $session,
        PaymentMeansProviderInterface $paymentMeansProvider
    ) {
        $this->session = $session;
        $this->paymentMeansProvider = $paymentMeansProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        $actionName = $args->getRequest()->getActionName();
        $isShippingPaymentView = 'shippingPayment' === $actionName && !$args->getRequest()->getParam('isXHR');
        if (!$isShippingPaymentView) {
            return;
        }

        $enrichedPaymentMeans = PaymentMeanCollection::createFromShopwareArray(($this->paymentMeansProvider)());
        $userData = $subject->View()->getAssign('sUserData');

        // if the stored method is not saved in session it means it was not selected in the payment step
        $storedMethodId = $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID);
        if (null === $storedMethodId) {
            $preselectedPaymentId = $userData['additional']['payment']['id'] ?? null;
            if (null === $preselectedPaymentId) {
                return;
            }

            $umbrellaPayment = $enrichedPaymentMeans->fetchStoredMethodUmbrellaPaymentMean();
            if (null === $umbrellaPayment) {
                // guest user won't have stored method
                return;
            }
            // but if the umbrella payment is in the user data it means a stored method was preselected by the user
            if ($umbrellaPayment->getId() !== (int) $preselectedPaymentId) {
                return;
            }
            // we use the saved user preference to get the stored method and allow the rest of the flow work normally
            $storedMethodId = $args->getSubject()->View()->getAssign('adyenUserPreference')['storedMethodId'] ?? null;
        }

        if (null === $storedMethodId) {
            return;
        }

        $paymentMean = $enrichedPaymentMeans->fetchByStoredMethodId($storedMethodId);
        if (null === $paymentMean) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        $userData['additional']['payment'] = $paymentMean->getRaw();
        $subject->View()->assign('sUserData', $userData);
        $subject->View()->assign('sFormData', ['payment' => $paymentMean->getValue('stored_method_umbrella_id')]);
    }
}
