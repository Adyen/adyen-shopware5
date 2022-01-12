<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class EnrichUmbrellaPaymentMeanSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;

    public function __construct(Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        $actionName = $subject->Request()->getActionName();
        $isShippingPaymentView = 'shippingPayment' === $actionName && !$subject->Request()->getParam('isXHR');
        if (!$isShippingPaymentView) {
            return;
        }

        $storedMethodId = $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID);
        if (null === $storedMethodId) {
            return;
        }

        $admin = Shopware()->Modules()->Admin();
        $enrichedPaymentMeans = PaymentMeanCollection::createFromShopwareArray($admin->sGetPaymentMeans());

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
