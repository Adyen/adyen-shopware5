<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Integration\Webhook\WebhookUrlService as BaseWebhookUrlService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use AdyenPayment\Utilities\Url;

/**
 * Class WebhookUrlService
 *
 * @package AdyenPayment\BusinessService
 */
class WebhookUrlService implements BaseWebhookUrlService
{
    /**
     * @var StoreContext
     */
    private $storeContext;

    /**
     * @var array
     */
    private static $callbackMap = [
        'host' => '',
        'replace' => '',
    ];

    /**
     * @param StoreContext $storeContext
     */
    public function __construct(StoreContext $storeContext)
    {
        $this->storeContext = $storeContext;
    }

    /**
     * @return string
     */
    public function getWebhookUrl(): string
    {
        $url = Url::getFrontUrl('AdyenWebhook', 'index', ['storeId' => $this->storeContext->getStoreId()]);

        // only for development purposes
        if (!empty(static::$callbackMap['host']) && !empty(static::$callbackMap['replace'])) {
            $url = str_replace(static::$callbackMap['host'], static::$callbackMap['replace'], $url);
        }

        return $url;
    }
}
