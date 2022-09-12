<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Shopware\Provider\PaymentMeansProviderInterface;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class EnrichUserAdditionalPaymentSubscriber implements SubscriberInterface
{
    /** @var EnrichedPaymentMeanProviderInterface */
    private $enrichedPaymentMeanProvider;

    /** @var PaymentMeansProviderInterface */
    private $paymentMeansProvider;

    /** @var Enlight_Components_Session_Namespace */
    private $session;

    public function __construct(
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider,
        PaymentMeansProviderInterface $paymentMeansProvider,
        Enlight_Components_Session_Namespace $session
    ) {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
        $this->paymentMeansProvider = $paymentMeansProvider;
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
        if ('confirm' !== $args->getRequest()->getActionName()) {
            return;
        }

        $storedMethodId = $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID);
        $userData = $subject->View()->getAssign('sUserData');
        $paymentMeanId = $userData['additional']['payment']['id'] ?? null;

        if (null === $storedMethodId && null === $paymentMeanId) {
            return;
        }

        $enrichedPaymentMeans = ($this->enrichedPaymentMeanProvider)(
            PaymentMeanCollection::createFromShopwareArray(($this->paymentMeansProvider)())
        );

        $paymentMean = null === $storedMethodId
            ? $enrichedPaymentMeans->fetchById((int) $paymentMeanId)
            : $enrichedPaymentMeans->fetchByStoredMethodId($storedMethodId);

        if (null === $paymentMean) {
            return;
        }

        $userData['additional']['payment'] = $paymentMean->getRaw();
        $subject->View()->assign('sUserData', $userData);
    }
}
