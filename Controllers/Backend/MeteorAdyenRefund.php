<?php

use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_MeteorAdyenRefund
    extends Shopware_Controllers_Backend_ExtJs
    implements CSRFWhitelistAware
{

    public function refundAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $notificationManager = $this->get('meteor_adyen.components.adyen.refund_service');
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
