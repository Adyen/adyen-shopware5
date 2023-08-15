<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use AdyenPayment\Components\PaymentMeansEnricher;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Shopware\Models\Customer\Customer;

final class EnrichUserAdditionalPaymentSubscriber implements SubscriberInterface
{
    /**
     * @var PaymentMeansEnricher
     */
    private $paymentMeansEnricher;
    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(
        PaymentMeansEnricher $paymentMeansEnricher,
        Enlight_Components_Session_Namespace $session
    ) {
        $this->paymentMeansEnricher = $paymentMeansEnricher;
        $this->session = $session;
    }

    /**
     * Run as early as possible but after @see AddStoredMethodUserPreferenceToView
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => ['__invoke', -99988],
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        if (!in_array($args->getRequest()->getActionName(), ['confirm', 'shippingPayment'])) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        $paymentMeanId = $userData['additional']['payment']['id'] ?? null;
        $this->session->offsetUnset('adyenEnrichedPaymentMean');

        if (null === $paymentMeanId) {
            return;
        }

        $storedMethodId = (string)$this->session->get('adyenStoredPaymentMethodId');
        if (empty($storedMethodId)) {
            $userPreferences = $subject->View()->getAssign('adyenUserPreference');
            $storedMethodId = $userPreferences['storedMethodId'] ?? '';
        }

        $enrichedPaymentMean = $this->paymentMeansEnricher->enrichPaymentMean(
            $userData['additional']['payment'],
            $storedMethodId
        );

        // Reset payment mean for guest checkout
        if (
            !empty($storedMethodId) &&
            (int)$userData['additional']['user']['accountmode'] === Customer::ACCOUNT_MODE_FAST_LOGIN
        ) {
            $enrichedPaymentMean = [];
        }

        // Keep enriched payment mean in session for services that do not have access to view (do not extend controllers)
        $this->session->offsetSet('adyenEnrichedPaymentMean', $enrichedPaymentMean);


        // Customer probably changed address to unsupported address for selected Adyen payment. Force payment selection.
        if (empty($enrichedPaymentMean)) {
            $subject->redirect(['controller' => 'checkout', 'action' => 'shippingPayment']);

            return;
        }

        $userData['additional']['payment'] = $enrichedPaymentMean;

        // Make sure that redirection from Amazon with session id has all confirmations checked
        if ($args->getRequest()->getParam('amazonCheckoutSessionId')) {
            $args->getRequest()->setParam('sAGB', true);
            $args->getRequest()->setParam('esdAgreementChecked', true);
            $args->getRequest()->setParam('serviceAgreementChecked', true);
        }

        $subject->View()->assign('sUserData', $userData);
        $subject->View()->assign('sPayment', $userData['additional']['payment']);
    }
}
