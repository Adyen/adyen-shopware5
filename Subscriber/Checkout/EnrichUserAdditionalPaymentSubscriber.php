<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Models\Payment\PaymentMean;
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

        $paymentMean = null;

        $storedMethodId = $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID);
        if (null !== $storedMethodId) {
            $enrichedPaymentMeans = ($this->enrichedPaymentMeanProvider)(
                PaymentMeanCollection::createFromShopwareArray(Shopware()->Modules()->Admin()->sGetPaymentMeans())
            );
            $paymentMean = $enrichedPaymentMeans->fetchByStoredMethodId($storedMethodId);
        }

        $userData = $subject->View()->getAssign('sUserData');
        if (null === $paymentMean) {
            $paymentMean = PaymentMean::createFromShopwareArray($userData['additional']['payment'] ?? []);
            $paymentMeans = ($this->enrichedPaymentMeanProvider)(new PaymentMeanCollection($paymentMean));
            $paymentMean = iterator_to_array($paymentMeans->getIterator())[0];
        }

        if (!$paymentMean->getId()) {
            return;
        }

        $userData['additional']['payment'] = $paymentMean->getRaw();
        $subject->View()->assign('sUserData', $userData);
    }
}
