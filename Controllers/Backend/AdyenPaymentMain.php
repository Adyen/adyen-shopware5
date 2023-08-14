<?php

use AdyenPayment\Utilities\Plugin;
use AdyenPayment\Utilities\Url;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Backend_AdyenPaymentMain
 */
class Shopware_Controllers_Backend_AdyenPaymentMain extends Enlight_Controller_Action implements CSRFWhitelistAware
{
	/**
	 * @inheritDoc
	 */
	public function getWhitelistedCSRFActions(): array
	{
		return ['index'];
	}

	/**
	 * Performs index action.
	 *
	 * @throws Exception
	 */
	public function indexAction(): void
	{
        $this->View()->assign([
            'response' => [
                'urls' => $this->getUrls(),
                'lang' => $this->getTranslations(),
                'sidebar' => $this->getSidebarContent(),
            ],
            'assetsVersion' => Plugin::getVersion(),
        ]);
	}

    private function getUrls(): array
    {
        return [
            'connection' => [
                'getSettingsUrl' => Url::getBackendUrl('AdyenAuthorization', 'getConnectionSettings') . '/storeId/{storeId}',
                'submitUrl' => Url::getBackendUrl('AdyenAuthorization', 'connect') . '/storeId/{storeId}',
                'disconnectUrl' => Url::getBackendUrl('AdyenDisconnect', 'disconnect') . '/storeId/{storeId}',
                'getMerchantsUrl' => Url::getBackendUrl('AdyenMerchant', 'index') . '/storeId/{storeId}',
                'validateConnectionUrl' => Url::getBackendUrl('AdyenValidateConnection', 'validate') . '/storeId/{storeId}',
                'validateWebhookUrl' => Url::getBackendUrl('AdyenWebhookValidation', 'validate') . '/storeId/{storeId}',
            ],
            'payments' => [
                'getConfiguredPaymentsUrl' => Url::getBackendUrl('AdyenPayment', 'getConfiguredMethods') . '/storeId/{storeId}',
                'addMethodConfigurationUrl' => Url::getBackendUrl('AdyenPayment', 'saveMethod') . '/storeId/{storeId}',
                'getMethodConfigurationUrl' => Url::getBackendUrl('AdyenPayment', 'getMethodById') . '/storeId/{storeId}/methodId/{methodId}',
                'saveMethodConfigurationUrl' => Url::getBackendUrl('AdyenPayment', 'updateMethod') . '/storeId/{storeId}',
                'getAvailablePaymentsUrl' => Url::getBackendUrl('AdyenPayment', 'getAvailableMethods') . '/storeId/{storeId}',
                'deleteMethodConfigurationUrl' => Url::getBackendUrl('AdyenPayment', 'deleteMethod') . '/storeId/{storeId}/methodId/{methodId}',
            ],
            'stores' => [
                'storesUrl' => Url::getBackendUrl('AdyenShopInformation', 'getStores'),
                'currentStoreUrl' => Url::getBackendUrl('AdyenShopInformation', 'getCurrentStore'),
            ],
            'integration' => [
                'stateUrl' => Url::getBackendUrl('AdyenState', 'index') . '/storeId/{storeId}',
            ],
            'version' => [
                'versionUrl' => Url::getBackendUrl('AdyenVersion', 'getVersion'),
            ],
            'settings' => [
                'getShippingStatusesUrl' => Url::getBackendUrl('AdyenOrderStatuses', 'getOrderStatuses') . '/storeId/{storeId}',
                'getSettingsUrl' => Url::getBackendUrl('AdyenGeneralSettings', 'getGeneralSettings') . '/storeId/{storeId}',
                'saveSettingsUrl' => Url::getBackendUrl('AdyenGeneralSettings', 'putGeneralSettings') . '/storeId/{storeId}',
                'getOrderMappingsUrl' => Url::getBackendUrl('AdyenOrderStatusMap', 'getOrderStatusMap') . '/storeId/{storeId}',
                'saveOrderMappingsUrl' => Url::getBackendUrl('AdyenOrderStatusMap', 'putOrderStatusMap') . '/storeId/{storeId}',
                'getGivingUrl' => Url::getBackendUrl('AdyenGivingSettings', 'getAdyenGivingSettings') . '/storeId/{storeId}',
                'saveGivingUrl' => Url::getBackendUrl('AdyenGivingSettings', 'putAdyenGivingSettings') . '/storeId/{storeId}',
                'webhookValidationUrl' => Url::getBackendUrl('AdyenWebhookValidation', 'validate') . '/storeId/{storeId}',
                'downloadWebhookReportUrl' => Url::getBackendUrl('AdyenWebhookValidation', 'validateReport') . '/storeId/{storeId}',
                'integrationValidationUrl' => Url::getBackendUrl('AdyenAutoTest', 'startAutoTest'),
                'integrationValidationTaskCheckUrl' => Url::getBackendUrl('AdyenAutoTest', 'autoTestStatus')  . '/queueItemId/{queueItemId}',
                'downloadIntegrationReportUrl' => Url::getBackendUrl('AdyenAutoTest', 'getReport'),
                'downloadSystemInfoFileUrl' => Url::getBackendUrl('AdyenSystemInfo', 'systemInfo'),
                'getSystemInfoUrl' => Url::getBackendUrl('AdyenDebug', 'getDebugMode'),
                'saveSystemInfoUrl' => Url::getBackendUrl('AdyenDebug', 'setDebugMode')
            ],
            'notifications' => [
                'getShopEventsNotifications' => Url::getBackendUrl('AdyenNotifications', 'getNotifications') . '/storeId/{storeId}',
                'getWebhookEventsNotifications' => Url::getBackendUrl('AdyenWebhookNotifications', 'getWebhookNotifications') . '/storeId/{storeId}'
            ]
        ];
    }

    /**
     * @return array
     */
    private function getTranslations(): array
    {
        return [
            'default' => $this->getDefaultTranslations(),
            'current' => $this->getCurrentTranslations(),
        ];
    }

    /**
     * @return false|string
     */
    private function getSidebarContent()
    {
        return file_get_contents(__DIR__ . '/../../Resources/views/backend/_resources/templates/sidebar.html');
    }

    /**
     * @return mixed
     */
    private function getDefaultTranslations()
    {
        $baseDir = __DIR__ . '/../../Resources/views/backend/_resources/lang/';

        return json_decode(file_get_contents($baseDir . 'en.json'), true);
    }

    /**
     * @return mixed
     */
    private function getCurrentTranslations()
    {
        $baseDir = __DIR__ . '/../../Resources/views/backend/_resources/lang/';
        $locale = $this->getLocale();

        return json_decode(file_get_contents($baseDir . $locale . '.json'), true);
    }

    /**
     * @return string
     */
    private function getLocale(): string
    {
        $locale = 'en';

        if ($auth = Shopware()->Container()->get('auth')) {
            $locale = substr($auth->getIdentity()->locale->getLocale, 0, 2);
        }

        return in_array($locale, ['en', 'de']) ? $locale : 'en';
    }
}
