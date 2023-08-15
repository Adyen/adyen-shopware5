<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

/**
 * Class Shopware_Controllers_Backend_AdyenWebhookValidation
 */
class Shopware_Controllers_Backend_AdyenWebhookValidation extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     *
     * @throws Exception
     */
    public function validateAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->webhookValidation($storeId)->validate();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function validateReportAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->webhookValidation($storeId)->report();

        $data = json_encode($result->toArray(), JSON_PRETTY_PRINT);
        $response = $this->Response();
        $response->headers->set('content-description', 'File Transfer');
        $response->headers->set('content-type', 'application/octet-stream');
        $response->headers->set('content-disposition', 'attachment; filename=webhook-validation.json');
        $response->headers->set('cache-control', 'public', true);
        $response->headers->set('content-length', (string)strlen($data));
        $response->sendHeaders();

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $out = fopen('php://output', 'wb');

        fwrite($out, $data);
        fclose($out);
    }
}
