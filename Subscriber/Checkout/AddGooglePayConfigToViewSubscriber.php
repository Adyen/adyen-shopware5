<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Components\WebComponents\ConfigContext;
use AdyenPayment\Components\WebComponents\ConfigProvider;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Models\Payment\PaymentType;
use AdyenPayment\Utils\JsonUtil;
use AdyenPayment\Utils\Sanitize;
use Enlight\Event\SubscriberInterface;

/**
 * Depends on EnrichUserAdditionalPaymentSubscriber.
 */
final class AddGooglePayConfigToViewSubscriber implements SubscriberInterface
{
    /** @var ConfigProvider */
    private $googlePayConfigProvider;

    public function __construct(ConfigProvider $googlePayConfigProvider)
    {
        $this->googlePayConfigProvider = $googlePayConfigProvider;
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
        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        $paymentMean = PaymentMean::createFromShopwareArray($userData['additional']['payment'] ?? []);
        if (!$paymentMean->getSource()->equals(SourceType::adyen())) {
            return;
        }

        $basket = $subject->View()->getAssign('sBasket');
        if (!$basket) {
            return;
        }

        $paymentType = $paymentMean->adyenType();
        if (!$paymentType || !$paymentType->equals(PaymentType::googlePay())) {
            return;
        }

        $googlePayConfig = ($this->googlePayConfigProvider)(ConfigContext::fromCheckoutEvent($args));
        $subject->View()->assign('sAdyenGoogleConfig',
            Sanitize::escape(JsonUtil::encode($googlePayConfig))
        );
    }
}
