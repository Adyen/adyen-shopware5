<?php

namespace AdyenPayment\Setup;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdyenAPI\Management\Connection\Http\Proxy as ConnectionProxy;
use Adyen\Core\BusinessLogic\AdyenAPI\Management\ProxyFactory;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Connection\Enums\Mode;
use Adyen\Core\BusinessLogic\Domain\Connection\Models\ApiCredentials;
use Adyen\Core\BusinessLogic\Domain\Connection\Models\ConnectionData;
use Adyen\Core\BusinessLogic\Domain\Connection\Models\ConnectionSettings;
use Adyen\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionSettingsRepository;
use Adyen\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\MethodAdditionalData\GooglePay;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\MethodAdditionalData\Oney;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\PaymentMethod;
use Adyen\Core\BusinessLogic\Domain\Payment\Repositories\PaymentMethodConfigRepository;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\TaskExecution\QueueService;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Repositories\Wrapper\StoreRepository;
use AdyenPayment\Utilities\Plugin;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Enlight_Components_Cron_Manager;
use Exception;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Snippet_Manager;

/**
 * Class Updater
 *
 * @package AdyenPayment\Setup
 */
class Updater
{
    /**
     * @var UpdateContext
     */
    private $context;
    /**
     * @var CachedConfigReader
     */
    private $configReader;
    /**
     * @var ConnectionService
     */
    private $connectionService;
    /**
     * @var StoreRepository
     */
    private $storeRepository;
    /**
     * @var PaymentMethodConfigRepository
     */
    private $paymentMethodConfigRepository;
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;
    /**
     * @var Enlight_Components_Cron_Manager
     */
    private $cronManager;
    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var ConnectionSettingsRepository
     */
    private $connectionSettingsRepository;

    /**
     * Updater constructor.
     *
     * @param UpdateContext $context
     * @param CachedConfigReader $configReader
     * @param ConnectionService $connectionService
     * @param StoreRepository $storeRepository
     * @param PaymentMethodConfigRepository $paymentMethodConfigRepository
     * @param Shopware_Components_Snippet_Manager $snippets
     * @param Enlight_Components_Cron_Manager $cronManager
     * @param QueueService $queueService
     * @param ConnectionSettingsRepository $connectionSettingsRepository
     */
    public function __construct(
        UpdateContext                       $context,
        CachedConfigReader                  $configReader,
        ConnectionService                   $connectionService,
        StoreRepository                     $storeRepository,
        PaymentMethodConfigRepository       $paymentMethodConfigRepository,
        Shopware_Components_Snippet_Manager $snippets,
        Enlight_Components_Cron_Manager     $cronManager,
        QueueService                        $queueService,
        ConnectionSettingsRepository        $connectionSettingsRepository
    )
    {
        $this->context = $context;
        $this->configReader = $configReader;
        $this->connectionService = $connectionService;
        $this->storeRepository = $storeRepository;
        $this->paymentMethodConfigRepository = $paymentMethodConfigRepository;
        $this->snippets = $snippets;
        $this->cronManager = $cronManager;
        $this->queueService = $queueService;
        $this->connectionSettingsRepository = $connectionSettingsRepository;
    }

    public function update(): void
    {
        $oldVersion = $this->context->getCurrentVersion();
        if (version_compare($oldVersion, '4.0.0', '<')) {
            $this->updateTo400();
        }
    }

    private function updateTo400(): void
    {
        $shops = $this->storeRepository->getShopwareSubShops();
        $shopsWithValidConnection = $this->migrateConnectionSettingsTo400($shops);
        $this->migratePaymentMeansTo400($shopsWithValidConnection);
        $this->migratePaymentMethodConfigsTo400($shopsWithValidConnection);
        $this->migrateCronsTo400();
        $this->initializeTransactionDetailsMigrationTo400();
        $this->deleteObsoleteConfiguration();
    }

    /**
     * Migrates connection configuration for list of shops provided.
     *
     * @param Shop[] $shops
     * @return Shop[] Shops where connection settings are migrated
     * @throws Exception
     */
    private function migrateConnectionSettingsTo400(array $shops): array
    {
        $migratedShops = [];
        foreach ($shops as $shop) {
            $config = $this->configReader->getByPluginName(AdyenPayment::NAME, $shop);
            if (empty($config)) {
                continue;
            }

            StoreContext::doWithStore(
                $shop->getId(),
                function () use ($shop, $config, &$migratedShops) {
                    try {
                        if ($this->migrateConnectionSettingsForShopTo400($shop, $config)) {
                            $migratedShops[] = $shop;
                        }
                    } catch (Exception $e) {
                        Logger::logWarning('Failed to migrate connection for ' . $shop->getName());
                    }
                }
            );
        }

        return $migratedShops;
    }

    /**
     * @param Shop[] $shopsWithValidConnection
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function migratePaymentMeansTo400(array $shopsWithValidConnection): void
    {
        $paymentMeans = $this->getAllAdyenPaymentMeans();

        if (empty($shopsWithValidConnection)) {
            foreach ($paymentMeans as $paymentMean) {
                // Skip already imported payments
                if (Plugin::isAdyenPaymentMean($paymentMean->getName())) {
                    continue;
                }
                $paymentMean->setActive(false);
                Shopware()->Models()->persist($paymentMean);
            }

            Shopware()->Models()->flush();

            return;
        }

        $languageStores = $this->storeRepository->getShopwareLanguageShops(array_map(static function (Shop $shop) {
            return $shop->getId();
        }, $shopsWithValidConnection));
        $paymentMeanShops = new ArrayCollection(array_merge($shopsWithValidConnection, $languageStores));

        foreach ($paymentMeans as $paymentMean) {
            // Skip already imported payments
            if (Plugin::isAdyenPaymentMean($paymentMean->getName())) {
                continue;
            }

            $enabledShops = [];
            $shops = $paymentMean->getShops();

            foreach ($shops->toArray() as $shop) {
                foreach ($paymentMeanShops->toArray() as $paymentMeanShop) {
                    if ($shop->getId() === $paymentMeanShop->getId()) {
                        $enabledShops[] = $shop;
                        continue 2;
                    }
                }
            }

            $code = $this->getAdyenPaymentMethodCode($paymentMean);
            if (!empty($code)) {
                $paymentMean
                    ->setName(Plugin::getPaymentMeanName($code))
                    ->setAction('AdyenPaymentProcess')
                    ->setShops(new ArrayCollection($enabledShops));
            } else {
                $paymentMean->setActive(false);
            }

            Shopware()->Models()->persist($paymentMean);
        }

        Shopware()->Models()->flush();
    }

    /**
     * @param Shop[] $shopsWithValidConnection
     * @return void
     * @throws Exception
     */
    private function migratePaymentMethodConfigsTo400(array $shopsWithValidConnection): void
    {
        $paymentMeans = $this->getAllAdyenPaymentMeans(true);
        if (empty($paymentMeans)) {
            return;
        }

        $oneyInstallmentsMap = [
            (string)PaymentMethodCode::facilyPay3x() => '3',
            (string)PaymentMethodCode::facilyPay4x() => '4',
            (string)PaymentMethodCode::facilyPay6x() => '6',
            (string)PaymentMethodCode::facilyPay10x() => '10',
            (string)PaymentMethodCode::facilyPay12x() => '12',
        ];
        $oneyActiveInstallments = [];
        $paymentMeansMap = [];
        foreach ($paymentMeans as $paymentMean) {
            $adyenPaymentCode = Plugin::getAdyenPaymentType($paymentMean->getName());
            $paymentMeansMap[$adyenPaymentCode] = $paymentMean;
            if (PaymentMethodCode::isOneyMethod($adyenPaymentCode)) {
                $paymentMeansMap[(string)PaymentMethodCode::oney()] = $paymentMean;
                $oneyActiveInstallments[] = $oneyInstallmentsMap[$adyenPaymentCode];
            }
        }

        foreach ($shopsWithValidConnection as $shop) {
            $adyenPaymentMethods = AdminAPI::get()->payment($shop->getId())->getAvailablePaymentMethods();
            $config = $this->configReader->getByPluginName(AdyenPayment::NAME, $shop);

            $configMerchantId = array_key_exists('merchant_account', $config) ? (string)$config['merchant_account'] : '';
            $googleMerchantId = array_key_exists('google_merchant_id', $config) ? (string)$config['google_merchant_id'] : '';
            $oneyIsConfigured = false;

            foreach ($adyenPaymentMethods->toArray() as $availablePaymentMethod) {
                if (!array_key_exists($availablePaymentMethod['code'], $paymentMeansMap)) {
                    continue;
                }

                if ($oneyIsConfigured && PaymentMethodCode::isOneyMethod($availablePaymentMethod['code'])) {
                    continue;
                }

                $shouldMigrate = false;
                foreach ($paymentMeansMap[$availablePaymentMethod['code']]->getShops() as $paymentMeanShop) {
                    if ($paymentMeanShop->getId() === $shop->getId()) {
                        $shouldMigrate = true;
                        break;
                    }
                }

                if (!$shouldMigrate) {
                    continue;
                }

                $paymentMethod = new PaymentMethod(
                    $availablePaymentMethod['methodId'],
                    $availablePaymentMethod['code'],
                    $availablePaymentMethod['name'],
                    $availablePaymentMethod['logo'],
                    $availablePaymentMethod['status'],
                    $availablePaymentMethod['currencies'],
                    $availablePaymentMethod['countries'],
                    $availablePaymentMethod['paymentType'],
                    'Adyen ' . $availablePaymentMethod['name'],
                    'none'
                );

                if (PaymentMethodCode::isOneyMethod($availablePaymentMethod['code'])) {
                    $oneyIsConfigured = true;
                    $paymentMethod->setAdditionalData(new Oney($oneyActiveInstallments));
                }

                if (
                    PaymentMethodCode::googlePay()->equals($availablePaymentMethod['code']) ||
                    PaymentMethodCode::payWithGoogle()->equals($availablePaymentMethod['code'])
                ) {
                    $paymentMethod->setAdditionalData(new GooglePay($configMerchantId, $googleMerchantId));
                }

                StoreContext::doWithStore(
                    $shop->getId(),
                    function () use ($paymentMethod) {
                        $this->paymentMethodConfigRepository->saveMethodConfiguration($paymentMethod);
                    }
                );
            }
        }
    }

    private function migrateCronsTo400(): void
    {
        $obsoleteCrons = [
            'Shopware_CronJob_AdyenPaymentProcessNotifications',
            'AdyenPayment_CronJob_ImportPaymentMethods'
        ];

        foreach ($obsoleteCrons as $obsoleteCron) {
            $cronJob = $this->cronManager->getJobByAction($obsoleteCron);
            if ($cronJob) {
                $this->cronManager->deleteJob($cronJob);
            }
        }
    }

    private function initializeTransactionDetailsMigrationTo400()
    {
        $this->queueService->enqueue('general_migration', new MigrateTransactionHistoryTask());

        // Shopware can show only one message as a result of the update, If message is already set, do not override it.
        $scheduled = $this->context->getScheduled();
        if (!empty($scheduled['message'])) {
            return;
        }

        /** @noinspection PhpParamsInspection */
        $this->context->scheduleMessage([
            'title' => $this->snippets
                ->getNamespace('backend/adyen/configuration')
                ->get('payment/adyen/update/transaction_history_info_title', 'Migration started', true),
            'text' => $this->snippets
                ->getNamespace('backend/adyen/configuration')
                ->get(
                    'payment/adyen/update/transaction_history_info_description',
                    'The migration of existing Adyen transactions has started in the background',
                    true
                ),
        ]);
    }

    /**
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function deleteObsoleteConfiguration()
    {
        $pluginId = $this->context->getPlugin()->getId();
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');

        /** @noinspection SqlDialectInspection */
        $sql = <<<SQL
DELETE s_core_config_forms, s_core_config_form_translations, s_core_config_elements, s_core_config_element_translations, s_core_config_values
FROM s_core_config_forms
LEFT JOIN s_core_config_form_translations ON s_core_config_form_translations.form_id = s_core_config_forms.id
LEFT JOIN s_core_config_elements ON s_core_config_elements.form_id = s_core_config_forms.id
LEFT JOIN s_core_config_element_translations ON s_core_config_element_translations.element_id = s_core_config_elements.id
LEFT JOIN s_core_config_values ON s_core_config_values.element_id = s_core_config_elements.id
WHERE s_core_config_forms.plugin_id = :pluginId
SQL;
        $connection->executeUpdate($sql, [':pluginId' => $pluginId]);
    }

    private function migrateConnectionSettingsForShopTo400(Shop $shop, $config): bool
    {
        $configMode = array_key_exists('environment', $config) ? $config['environment'] : '';
        $configMerchantId = array_key_exists('merchant_account', $config) ? (string)$config['merchant_account'] : '';
        $configTestApiKey = array_key_exists('api_key_test', $config) ? (string)$config['api_key_test'] : '';
        $configLiveApiKey = array_key_exists('api_key_live', $config) ? (string)$config['api_key_live'] : '';
        $configApiUrlPrefix = array_key_exists('api_url_prefix', $config) ? (string)$config['api_url_prefix'] : '';

        if (empty($configMode) || empty($configMerchantId)) {
            return false;
        }

        if (empty($configTestApiKey) && empty($configLiveApiKey)) {
            return false;
        }

        $liveConnectionData = $this->getLiveConnectionData(
            $shop, $configMerchantId, $configLiveApiKey, $configApiUrlPrefix
        );
        $testConnectionData = $this->getTestConnectionData($shop, $configMerchantId, $configTestApiKey);

        if (!$liveConnectionData && !$testConnectionData) {
            return false;
        }

        $connectionSettings = new ConnectionSettings(
            $shop->getId(),
            'LIVE' === mb_strtoupper($configMode) ? Mode::MODE_LIVE : Mode::MODE_TEST,
            $testConnectionData,
            $liveConnectionData
        );

        return $this->initializeConnection($shop, $connectionSettings);
    }

    private function getLiveConnectionData(
        Shop    $shop,
        string  $configMerchantId,
        ?string $configLiveApiKey = '',
        ?string $configApiUrlPrefix = ''
    ): ?ConnectionData
    {
        if (empty($configLiveApiKey) || empty($configApiUrlPrefix)) {
            return null;
        }

        $liveApiCredentials = $this->getApiCredentialsFor(new ConnectionSettings(
            $shop->getId(),
            Mode::MODE_LIVE,
            null,
            new ConnectionData($configLiveApiKey, $configMerchantId, $configApiUrlPrefix)
        ));

        if (!$liveApiCredentials) {
            return null;
        }

        return new ConnectionData(
            $configLiveApiKey,
            $configMerchantId,
            $configApiUrlPrefix,
            '',
            $liveApiCredentials
        );
    }

    private function getTestConnectionData(
        Shop    $shop,
        string  $configMerchantId,
        ?string $configTestApiKey = ''
    ): ?ConnectionData
    {
        if (empty($configTestApiKey)) {
            return null;
        }

        $testApiCredentials = $this->getApiCredentialsFor(new ConnectionSettings(
            $shop->getId(),
            Mode::MODE_TEST,
            new ConnectionData($configTestApiKey, $configMerchantId),
            null
        ));

        if (!$testApiCredentials) {
            return null;
        }

        return new ConnectionData(
            $configTestApiKey,
            $configMerchantId,
            '',
            '',
            $testApiCredentials
        );
    }

    private function getApiCredentialsFor(ConnectionSettings $connectionSettings): ?ApiCredentials
    {
        $apiCredentials = $this->getProxy($connectionSettings)->getApiCredentialDetails();

        if (!$apiCredentials || !$apiCredentials->isActive()) {
            return null;
        }

        return $apiCredentials;
    }

    private function initializeConnection(Shop $shop, ConnectionSettings $connectionSettings): bool
    {
        try {
            $this->connectionSettingsRepository->setConnectionSettings($connectionSettings);
            $this->connectionService->saveConnectionData($connectionSettings);
        } catch (Exception $e) {
            Logger::logWarning('Migration of connection settings failed for store ' . $shop->getId()
                . ' because ' . $e->getMessage());


            if ($connectionSettings->getMode() === Mode::MODE_LIVE) {
                $settings = new ConnectionSettings(
                    $connectionSettings->getStoreId(),
                    $connectionSettings->getMode(),
                    null,
                    new ConnectionData(
                        $connectionSettings->getLiveData()->getApiKey(),
                        '',
                        $connectionSettings->getLiveData()->getClientPrefix()
                    )
                );
            } else {
                $settings = new ConnectionSettings(
                    $connectionSettings->getStoreId(),
                    $connectionSettings->getMode(),
                    new ConnectionData($connectionSettings->getTestData()->getApiKey(), ''),
                    null
                );
            }

            $this->connectionSettingsRepository->setConnectionSettings($settings);
            $this->showInvalidSettingsWarning($shop);

            return false;
        }

        return true;
    }

    /**
     * @return Payment[]
     */
    private function getAllAdyenPaymentMeans($onlyActive = false): array
    {
        $query = Shopware()->Models()->getRepository(Payment::class)
            ->createQueryBuilder('paymentmeans')
            ->where('paymentmeans.source = :source')
            ->setParameter('source', AdyenPayment::PAYMENT_METHOD_SOURCE);

        if ($onlyActive) {
            $query->andWhere('paymentmeans.active = 1');
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Gets Adyen payment method code based on previous payment mean generated in shop
     *
     * @param Payment $paymentMean
     * @return string
     */
    private function getAdyenPaymentMethodCode(Payment $paymentMean): string
    {
        $paymentMeanName = $paymentMean->getName();

        // Adjustment for gift card brands
        if (0 === stripos($paymentMeanName, 'giftcard_')) {
            $paymentMeanName = str_replace(['giftcard_', '_'], '', $paymentMeanName);
        }

        if ('eagleeyevoucher' === $paymentMeanName) {
            $paymentMeanName = 'eagleeye_voucher';
        }

        // Sort methods so that longer codes are before shorter ones (handle cases like klarna and klarna_paynow)
        $supportedPaymentMethods = PaymentMethodCode::SUPPORTED_PAYMENT_METHODS;
        rsort($supportedPaymentMethods, SORT_STRING);

        foreach ($supportedPaymentMethods as $code) {
            // Gift cards are matched by brand, skip umbrella payment method code
            if (PaymentMethodCode::giftCard()->equals($code)) {
                continue;
            }

            // Old payment mean name is concatenation of adyen payment method code and underscored name
            // (e.g. klarna_paynow_pay_now_with_klarna or scheme_credit_card)
            if (0 === stripos($paymentMeanName, $code)) {
                return $code;
            }
        }

        return '';
    }

    private function showInvalidSettingsWarning(Shop $shop): void
    {
        $moduleLink = sprintf(
            'javascript:sessionStorage.setItem("adl-active-store-id", "%s");
                Shopware.ModuleManager.createSimplifiedModule("AdyenPaymentMain#connection", {
                    title: "Adyen",
                    maximized: true
                });',
            $shop->getId()
        );

        /** @noinspection PhpParamsInspection */
        $this->context->scheduleMessage([
            'title' => sprintf(
                $this->snippets
                    ->getNamespace('backend/adyen/configuration')
                    ->get('payment/adyen/update/api_key_warning_title', 'Insufficient scope detected (%s)', true),
                $shop->getName()
            ),
            'text' => $this->snippets
                ->getNamespace('backend/adyen/configuration')
                ->get(
                    'payment/adyen/update/api_key_warning_description',
                    'Please reauthenticate with a new API key in order to continue using the Adyen plugin seamlessly',
                    true
                ),
            'btnDetail' => [
                'text' => $this->snippets
                    ->getNamespace('backend/adyen/configuration')
                    ->get(
                        'payment/adyen/update/api_key_warning_open_button_text',
                        'Open Adyen',
                        true
                    ),
                'link' => $moduleLink,
                'target' => '_self'
            ]
        ]);
    }

    private function getProxy(ConnectionSettings $connectionSettings): ConnectionProxy
    {
        return ProxyFactory::makeProxy(ConnectionProxy::class, $connectionSettings);
    }
}
