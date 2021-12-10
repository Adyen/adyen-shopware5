<?php

use Shopware\Components\CSRFWhitelistAware;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
class Shopware_Controllers_Backend_AdyenPaymentRefund extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    public function refundAction(): void
    {
        $orderId = $this->Request()->getParam('orderId');
        $notificationManager = $this->get('AdyenPayment\Components\Adyen\RefundService');
        $refund = $notificationManager->doRefund($orderId);

        $this->View()->assign('refundReference', $refund->getPspReference());
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     *
     * @psalm-return array{0: 'refund'}
     */
    public function getWhitelistedCSRFActions()
    {
        return ['refund'];
    }
}
