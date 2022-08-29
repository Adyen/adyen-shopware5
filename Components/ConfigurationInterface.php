<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Shopware\Models\Shop\Shop;

interface ConfigurationInterface
{
    public function getEnvironment($shop = false): string;
    public function isTestMode($shop = false): bool;
    public function getMerchantAccount($shop = false): string;
    public function getConfig(?string $key = null, $shop = false);
    public function getApiKey($shop = false): string;
    public function getApiUrlPrefix($shop = false): string;
    public function getClientKey(Shop $shop): string;
    public function getNotificationHmac($shop = false): string;
    public function getNotificationAuthUsername($shop = false): string;
    public function getNotificationAuthPassword($shop = false): string;
    public function getGoogleMerchantId($shop = false): string;
    public function isPaymentmethodsCacheEnabled($shop = false): bool;
    public function getManualReviewRejectAction($shop = false): string;
    public function getCurrentPluginVersion(): int;
}
