<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class EnrichUserAdditionalPaymentSubscriber implements SubscriberInterface
{
    private EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider;
    private Enlight_Components_Session_Namespace $session;

    public function __construct(
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider,
        Enlight_Components_Session_Namespace $session
    ) {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run as early as possible, before AddGooglePayConfigToViewSubscriber
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => ['__invoke', -99999],
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        $storedMethodId = $this->session->get('storedMethodId');
        $userData = $subject->View()->getAssign('sUserData');
        $paymentMeanId = $userData['additional']['payment']['id'] ?? null;

        if (null === $storedMethodId && null === $paymentMeanId) {
            return;
        }

        $admin = Shopware()->Modules()->Admin();
        $enrichedPaymentMeans = ($this->enrichedPaymentMeanProvider)(
            PaymentMeanCollection::createFromShopwareArray($admin->sGetPaymentMeans())
        );

        $paymentMean = null === $storedMethodId
            ? $enrichedPaymentMeans->fetchById((int) $paymentMeanId)
            : $enrichedPaymentMeans->fetchByStoredMethodUmbrellaId($storedMethodId);
        if (null === $paymentMean) {
            return;
        }

        $userData['additional']['payment'] = $paymentMean->getRaw();
        $subject->View()->assign('sUserData', $userData);
    }
}
