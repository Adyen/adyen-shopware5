<?php

class Shopware_Controllers_Frontend_AdyenClickToPay extends Shopware_Controllers_Frontend_Payment
{
    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @return void
     *
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->session = $this->get('session');
    }

    public function indexAction()
    {
        $this->view->assign('action', $this->session->offsetGet('adyen_action'));
        $this->view->assign('basketSignature', $this->session->offsetGet('signature'));
        $this->view->assign('orderReference', $this->session->offsetGet('orderReference'));
    }
}
