<?php

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Webhook\Exceptions\WebhookConfigDoesntExistException;
use Adyen\Webhook\Exception\AuthenticationException;
use Adyen\Webhook\Exception\HMACKeyValidationException;
use Adyen\Webhook\Exception\InvalidDataException;
use Adyen\Webhook\Exception\MerchantAccountCodeException;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use Shopware\Components\CSRFWhitelistAware;
use Adyen\Core\BusinessLogic\WebhookAPI\WebhookAPI;

/**
 * Class Shopware_Controllers_Frontend_AdyenWebhook
 */
class Shopware_Controllers_Frontend_AdyenWebhook extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use AjaxResponseSetter;

    /**
     * @param $request
     * @param $response
     *
     * @return void
     *
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Exception
     */
    public function initController($request, $response): void
    {
        $this->Front()->Plugins()->JsonRequest()
            ->setParseInput();

        parent::initController($request, $response);
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions(): array
    {
        return ['index'];
    }

    /**
     * Handles webhook request.
     *
     * @return void
     *
     * @throws InvalidCurrencyCode
     * @throws WebhookConfigDoesntExistException
     * @throws AuthenticationException
     * @throws HMACKeyValidationException
     * @throws InvalidDataException
     * @throws MerchantAccountCodeException
     */
    public function indexAction(): void
    {
        $payload = $this->Request()->getParams();
        $result = WebhookAPI::get()->webhookHandler($payload['storeId'] ?? '')->handleRequest($payload);

        $this->returnAPIResponse($result);
    }
}
