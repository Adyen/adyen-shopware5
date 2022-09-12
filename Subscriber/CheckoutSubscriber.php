<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\DataConversion;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Serializer\PaymentMeanCollectionSerializer;
use AdyenPayment\Shopware\Provider\CheckoutBasketProviderInterface;
use Enlight\Event\SubscriberInterface;

final class CheckoutSubscriber implements SubscriberInterface
{
    /** @var Configuration */
    private $configuration;

    /** @var PaymentMethodService */
    private $paymentMethodService;

    /** @var DataConversion */
    private $dataConversion;

    /** @var EnrichedPaymentMeanProviderInterface */
    private $enrichedPaymentMeanProvider;

    /** @var PaymentMethodOptionsBuilderInterface */
    private $paymentMethodOptionsBuilder;

    /** @var PaymentMeanCollectionSerializer */
    private $paymentMeanCollectionSerializer;

    /** @var CheckoutBasketProviderInterface */
    private $checkoutBasketProvider;

    public function __construct(
        Configuration $configuration,
        PaymentMethodService $paymentMethodService,
        DataConversion $dataConversion,
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider,
        PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder,
        PaymentMeanCollectionSerializer $paymentMeanCollectionSerializer,
        CheckoutBasketProviderInterface $checkoutBasketProvider
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
        $this->dataConversion = $dataConversion;
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
        $this->paymentMethodOptionsBuilder = $paymentMethodOptionsBuilder;
        $this->paymentMeanCollectionSerializer = $paymentMeanCollectionSerializer;
        $this->checkoutBasketProvider = $checkoutBasketProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();

        $this->checkBasketAmount($subject);
        $this->checkFirstCheckoutStep($subject);
    }

    private function checkBasketAmount(\Enlight_Controller_Action $subject): void
    {
        if ('finish' === $subject->Request()->getActionName()) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');

        $paymentMean = PaymentMean::createFromShopwareArray($userData['additional']['payment'] ?? []);
        if (!$paymentMean->getSource()->equals(SourceType::adyen())) {
            return;
        }

        $basket = ($this->checkoutBasketProvider)();

        if (!$basket) {
            return;
        }
        $value = $basket['sAmount'];
        if (empty($value)) {
            $this->revertToDefaultPaymentMethod($subject);
        }
    }

    private function checkFirstCheckoutStep(\Enlight_Controller_Action $subject): void
    {
        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        if ($this->shouldRedirectToStep2($subject)) {
            $subject->forward('shippingPayment', 'checkout');
        }
    }

    private function shouldRedirectToStep2(\Enlight_Controller_Action $subject): bool
    {
        $userData = $subject->View()->getAssign('sUserData');
        $swPaymentMean = PaymentMean::createFromShopwareArray($userData['additional']['payment'] ?? []);
        if (!$swPaymentMean->isAdyenSourceType()) {
            return false;
        }

        $paymentMethodOptions = ($this->paymentMethodOptionsBuilder)();
        if (0 === (int) $paymentMethodOptions['value']) {
            return false;
        }

        $adyenPaymentMethods = $this->paymentMethodService->getPaymentMethods(
            $paymentMethodOptions['countryCode'],
            $paymentMethodOptions['currency'],
            $paymentMethodOptions['value']
        );

        $adyenPaymentMethod = $adyenPaymentMethods->fetchByPaymentMean($swPaymentMean);
        if (!$adyenPaymentMethod) {
            return true;
        }

        // @TODO Adyen Checkout API 68 'details' are removed
        if (!$adyenPaymentMethod->hasDetails() && !$adyenPaymentMethod->isStoredPayment()) {
            $subject->View()->assign('adyenPaymentState', $adyenPaymentMethod->serializeMinimalState());

            return false;
        }

        return false;
    }

    private function revertToDefaultPaymentMethod(\Enlight_Controller_Action $subject): void
    {
        $defaultPaymentId = Shopware()->Config()->get('defaultPayment');
        $defaultPayment = Shopware()->Modules()->Admin()->sGetPaymentMeanById($defaultPaymentId);
        if (Shopware()->Modules()->Admin()->sUpdatePayment($defaultPaymentId)) {
            // Replace Adyen payment method in the template with the default payment method.
            $userData = $subject->View()->getAssign('sUserData');
            $userData['additional']['payment'] = $defaultPayment;
            $subject->View()->assign('sUserData', $userData);
            $subject->View()->assign('sPayment', $defaultPayment);
            $subject->View()->clearAssign('adyenPaymentState');
        }
    }
}
