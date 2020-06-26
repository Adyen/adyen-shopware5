<?php

use Shopware\Components\CSRFWhitelistAware;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
class Shopware_Controllers_Backend_AdyenPaymentRefund extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    public function refundAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $notificationManager = $this->get('adyen_payment.components.adyen.refund_service');
        $refund = $notificationManager->doRefund($orderId);

        $this->View()->assign('refundReference', $refund->getPspReference());
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['refund'];
    }
}
