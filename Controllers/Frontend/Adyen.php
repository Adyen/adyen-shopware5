<?php

class Shopware_Controllers_Frontend_Adyen extends Enlight_Controller_Action
{
    public function index()
    {
        die('hello');
    }

    public function ajaxDoPaymentAction()
    {
        // $this->Request()->setHeader('Content-Type', 'application/json');
        // $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $paymentMethod = json_decode($this->Request()->getPost('paymentMethod'));

        $params = array(
            "amount" => array(
                "currency" => "EUR",
                "value" => 1000
            ),
            "reference" => "YOUR_ORDER_NUMBER",
            "paymentMethod" => $paymentMethod,
            "returnUrl" => "https://your-company.com/checkout?shopperOrder=12xy..",
            "merchantAccount" => "YOUR_MERCHANT_ACCOUNT"
        );

        $result = $service->payments($params);


        $this->Response()->setBody(json_encode(
            [
                'success' => true,
            ]
        ));
    }
}
