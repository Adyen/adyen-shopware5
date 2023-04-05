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
abstract class BaseAddPaymentMethodConfigToViewSubscriber implements SubscriberInterface
{
    /** @var ConfigProvider */
    protected $paymentMethodConfigProvider;

    /** @var PaymentType */
    protected $paymentType;

    /** @var string */
    protected $paymentConfigViewKey;

    public function __construct(ConfigProvider $paymentMethodConfigProvider)
    {
        $this->paymentMethodConfigProvider = $paymentMethodConfigProvider;
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
        if (!$paymentType || !$paymentType->equals($this->getPaymentType())) {
            return;
        }

        $paymentConfig = ($this->paymentMethodConfigProvider)(ConfigContext::fromCheckoutEvent($args));
        $subject->View()->assign(
            $this->getPaymentMethodViewKey(),
            Sanitize::escape(JsonUtil::encode($paymentConfig))
        );
    }

    protected function getPaymentType(): PaymentType
    {
        return $this->paymentType;
    }

    protected function getPaymentMethodViewKey(): string
    {
        return $this->paymentConfigViewKey;
    }
}
