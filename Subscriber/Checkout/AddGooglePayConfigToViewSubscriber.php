<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\WebComponents\ConfigContext;
use AdyenPayment\Components\WebComponents\ConfigProvider;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight\Event\SubscriberInterface;

final class AddGooglePayConfigToViewSubscriber implements SubscriberInterface
{
    private static $PAY_WITH_GOOGLE = 'paywithgoogle';
    private Configuration $configuration;
    private ConfigProvider $googlePayConfigProvider;

    public function __construct(Configuration $configuration, ConfigProvider $googlePayConfigProvider)
    {
        $this->configuration = $configuration;
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
        $source = (int) ($userData['additional']['payment']['source'] ?? null);
        if (!SourceType::load($source)->equals(SourceType::adyen())) {
            return;
        }

        $basket = $subject->View()->getAssign('sBasket');
        if (!$basket) {
            return;
        }

        $adyenType = (string) ($userData['additional']['payment']['attributes']['core']['adyen_type'] ?? '');
        if (self::$PAY_WITH_GOOGLE !== $adyenType) {
            return;
        }

        $googlePayConfig = ($this->googlePayConfigProvider)(ConfigContext::fromCheckoutEvent($args));
        $subject->View()->assign('sAdyenGoogleConfig', htmlentities(
                json_encode($googlePayConfig, JSON_THROW_ON_ERROR))
        );
    }
}
