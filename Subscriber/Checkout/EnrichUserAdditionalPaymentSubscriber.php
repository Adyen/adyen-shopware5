<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Models\Payment\PaymentMean;
use Enlight\Event\SubscriberInterface;

final class EnrichUserAdditionalPaymentSubscriber implements SubscriberInterface
{
    private EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider;

    public function __construct(EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider)
    {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run before AddGooglePayConfigToViewSubscriber
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => ['__invoke', 99999],
        ];
    }

    public function __invoke(\Enlight_Event_EventArgs $args): void
    {
        $subject = $args->getSubject();
        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        $paymentMean = PaymentMean::createFromShopwareArray(
            $subject->View()->getAssign('sUserData')['additional']['payment'] ?? []
        );
        if (!$paymentMean->getId()) {
            return;
        }

        $paymentMeans = ($this->enrichedPaymentMeanProvider)(new PaymentMeanCollection($paymentMean));
        /** @var PaymentMean $paymentMean */
        $paymentMean = iterator_to_array($paymentMeans->getIterator())[0];
        $userData['additional']['payment'] = $paymentMean->getRaw();
        $subject->View()->assign('sUserData', $userData);
    }
}
