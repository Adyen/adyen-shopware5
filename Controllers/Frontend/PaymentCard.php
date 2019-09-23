<?php

class Shopware_Controllers_Frontend_PaymentCard extends Shopware_Controllers_Frontend_Payment
{
    public function indexAction()
    {
        switch ($this->getPaymentShortName()) {
            case \MeteorAdyen\MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD:
                return $this->redirect(['controller' => 'paymentcard', 'action' => 'component', 'forceSecure' => true]);
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    public function componentAction()
    {
        // todo
    }

    public function returnAction()
    {
        // todo
    }

    public function cancelAction()
    {
        // todo
    }
}
