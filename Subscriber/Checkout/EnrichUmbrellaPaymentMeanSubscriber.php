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

        $storedMethodId = $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID);
        if (null === $storedMethodId) {
            return;
        }

        $enrichedPaymentMeans = PaymentMeanCollection::createFromShopwareArray(($this->paymentMeansProvider)());

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
