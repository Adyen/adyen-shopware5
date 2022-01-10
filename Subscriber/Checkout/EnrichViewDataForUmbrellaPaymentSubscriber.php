<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Models\Payment\PaymentMean;
use Enlight\Event\SubscriberInterface;

final class EnrichViewDataForUmbrellaPaymentSubscriber implements SubscriberInterface
{
    private EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider;

    public function __construct(EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider)
    {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        /**
         * @TODO - ASW-377: WIP, technically this is the same as EnrichUserAdditionalPaymentSubscriber.
         *      Created a new subscriber to easily remove it if not needed, but at the end we need to fix the ID for
         *      the shippingPaymentAction as well.
         */
        $subject = $args->getSubject();
        if ('shippingPayment' !== $subject->Request()->getActionName() || $subject->Request()->getParam('isXHR')) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData') ?? [];

        // @TODO: WIP - TEST.
        $paymentMean = PaymentMean::createFromShopwareArray(
            $userData['additional']['payment'] ?? []
        );
        if (!$paymentMean->getId()) {
            return;
        }

        $isUmbrellaMethod = AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE === $paymentMean->getValue('name');
        if (!$isUmbrellaMethod) {
            return;
        }

        $paymentMeans = ($this->enrichedPaymentMeanProvider)(new PaymentMeanCollection($paymentMean));
        /** @var PaymentMean $paymentMean */
        $paymentMean = iterator_to_array($paymentMeans->getIterator())[0];
        $userData['additional']['payment'] = $paymentMean->getRaw();

        $storedMethodUmbrellaId = $userData['additional']['payment']['stored_method_umbrella_id'] ?? null;
        if (null === $storedMethodUmbrellaId) {
            return;
        }

        $subject->View()->assign('sFormData', ['payment' => $storedMethodUmbrellaId]);
    }
}
