<?php

use MeteorAdyen\Components\Manager\AdyenManager;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Redirect
 */
class Shopware_Controllers_Frontend_Process extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @var AdyenManager
     */
    private $adyenManager;

    /**
     * @var \MeteorAdyen\Components\Adyen\PaymentMethodService
     */
    private $adyenCheckout;

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return'];
    }


    public function preDispatch()
    {
        $this->adyenManager = $this->get('meteor_adyen.components.manager.adyen_manager');
        $this->adyenCheckout = $this->get('meteor_adyen.components.adyen.payment.method');
    }

    /**
     * @throws Exception
     */
    public function returnAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $response = $this->Request()->getPost();

        if ($response) {
            $result = $this->validateResponse($response);
            if ($result) {
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
            }
        }
    }

    /**
     * Validates the payload from checkout /payments hpp and returns the api response
     *
     * @param $response
     * @return mixed
     */
    private function validateResponse($response)
    {
        $request['paymentData'] = $this->adyenManager->getPaymentDataSession();
        $request['details'] = $response;

        try {
            $checkout = $this->adyenCheckout->getCheckout();
            $response = $checkout->paymentsDetails($request);
        } catch (\Adyen\AdyenException $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
