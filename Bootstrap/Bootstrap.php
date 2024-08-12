<?php

namespace AdyenPayment\Bootstrap;

use Adyen\Core\BusinessLogic\Bootstrap\SingleInstance;
use Adyen\Core\BusinessLogic\BootstrapComponent;
use Adyen\Core\BusinessLogic\DataAccess\AdyenGiving\Entities\DonationsData;
use Adyen\Core\BusinessLogic\DataAccess\AdyenGivingSettings\Entities\AdyenGivingSettings;
use Adyen\Core\BusinessLogic\DataAccess\Connection\Entities\ConnectionSettings;
use Adyen\Core\BusinessLogic\DataAccess\Disconnect\Entities\DisconnectTime;
use Adyen\Core\BusinessLogic\DataAccess\GeneralSettings\Entities\GeneralSettings;
use Adyen\Core\BusinessLogic\DataAccess\Notifications\Entities\Notification;
use Adyen\Core\BusinessLogic\DataAccess\OrderSettings\Entities\OrderStatusMapping;
use Adyen\Core\BusinessLogic\DataAccess\Payment\Entities\PaymentMethod;
use Adyen\Core\BusinessLogic\DataAccess\TransactionHistory\Entities\TransactionHistory;
use Adyen\Core\BusinessLogic\DataAccess\TransactionLog\Entities\TransactionLog;
use Adyen\Core\BusinessLogic\DataAccess\Webhook\Entities\WebhookConfig;
use Adyen\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Services\GeneralSettingsService;
use Adyen\Core\BusinessLogic\Domain\Integration\Order\OrderService as OrderServiceInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\AddressProcessor as PaymentLinkAddressProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\LineItemsProcessor as PaymentLinkLineItemsProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperBirthdayProcessor as PaymentLinkShopperBirthdayProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperEmailProcessor as PaymentLinkShopperEmailProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperLocaleProcessor as PaymentLinkShopperLocaleProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperNameProcessor as PaymentLinkShopperNameProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\ShopperReferenceProcessor as PaymentLinkShopperReferenceProcessorInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\AddressProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\BasketItemsProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\BirthdayProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\L2L3DataProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\LineItemsProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\ShopperEmailProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\ShopperLocaleProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\ShopperNameProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService as StoreServiceInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\SystemInfo\SystemInfoService as SystemInfoServiceInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use Adyen\Core\BusinessLogic\Domain\Integration\Webhook\WebhookUrlService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Repositories\PaymentMethodConfigRepository;
use Adyen\Core\BusinessLogic\Domain\Payment\Services\PaymentService;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionDetailsService as BaseTransactionDetailsServiceAlias;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\BusinessLogic\Domain\Webhook\Services\WebhookSynchronizationService as WebhookSynchronizationServiceInterface;
use Adyen\Core\BusinessLogic\Webhook\Handler\WebhookHandler;
use Adyen\Core\Infrastructure\Configuration\ConfigEntity;
use Adyen\Core\Infrastructure\Configuration\Configuration;
use Adyen\Core\Infrastructure\Http\CurlHttpClient;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Adyen\Core\Infrastructure\Logger\LogData;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Adyen\Core\Infrastructure\ORM\RepositoryRegistry;
use Adyen\Core\Infrastructure\Serializer\Concrete\JsonSerializer;
use Adyen\Core\Infrastructure\Serializer\Serializer;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Core\Infrastructure\TaskExecution\Process;
use Adyen\Core\Infrastructure\TaskExecution\QueueItem;
use Adyen\Core\Infrastructure\TaskExecution\QueueService;
use AdyenPayment\Components\Configuration\ConfigurationService;
use AdyenPayment\Components\Integration\FileService;
use AdyenPayment\Components\Integration\LegacyMerchantReferenceNormalizationWebhookHandler;
use AdyenPayment\Components\Integration\OrderService;
use AdyenPayment\Components\Integration\PaymentMethodService;
use AdyenPayment\Components\Integration\PaymentProcessors\AddressProcessor as IntegrationAddressProcessor;
use AdyenPayment\Components\Integration\PaymentProcessors\BirthdayProcessor as IntegrationBirthdayProcessor;
use AdyenPayment\Components\Integration\PaymentProcessors\L2L3DataProcessor as IntegrationL2L3DataProcessor;
use AdyenPayment\Components\Integration\PaymentProcessors\LineItemsProcessor as IntegrationLineItemsProcessor;
use AdyenPayment\Components\Integration\PaymentProcessors\ShopperEmailProcessor as IntegrationShopperEmailProcessor;
use AdyenPayment\Components\Integration\PaymentProcessors\ShopperLocaleProcessor as IntegrationShopperLocaleProcessor;
use AdyenPayment\Components\Integration\PaymentProcessors\ShopperNameProcessor as IntegrationShopperNameProcessor;
use AdyenPayment\Components\Integration\PaymentProcessors\ShopperReferenceProcessor as IntegrationShopperReferenceProcessor;
use AdyenPayment\Components\Integration\StoreService;
use AdyenPayment\Components\Integration\SystemInfoService;
use AdyenPayment\Components\LastOpenTimeService;
use AdyenPayment\Components\Logger\LoggerService;
use AdyenPayment\Components\TransactionDetailsService;
use AdyenPayment\Components\UninstallService;
use AdyenPayment\Entities\LastOpenTime;
use AdyenPayment\Repositories\AdyenGivingRepository;
use AdyenPayment\Repositories\BaseRepository;
use AdyenPayment\Repositories\BaseRepositoryWithConditionalDeletes;
use AdyenPayment\Repositories\NotificationsRepository;
use AdyenPayment\Repositories\PaymentMethodRepository;
use AdyenPayment\Repositories\QueueItemRepository;
use AdyenPayment\Repositories\TransactionLogRepository;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use AdyenPayment\Repositories\Wrapper\StoreRepository;
use AdyenPayment\Services\CustomerService;
use Shopware\Models\Article\Article;
use Shopware\Models\Country\Country;

/**
 * Class Bootstrap
 *
 * @package AdyenPayment\Bootstrap
 */
class Bootstrap extends BootstrapComponent
{
    /**
     * @inheritDoc
     */
    protected static function initServices(): void
    {
        parent::initServices();

        ServiceRegister::registerService(
            Serializer::CLASS_NAME,
            static function () {
                return new JsonSerializer();
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            static function () {
                return Shopware()->Container()->get(LoggerService::class);
            }
        );

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            static function () {
                return ConfigurationService::getInstance();
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            static function () {
                return new CurlHttpClient();
            }
        );

        ServiceRegister::registerService(
            FileService::class,
            static function () {
                return new FileService(Shopware()->Container()->get('shopware_media.media_service'));
            }
        );

        ServiceRegister::registerService(
            OrderServiceInterface::class,
            static function () {
                return Shopware()->Container()->get(OrderService::class);
            }
        );

        ServiceRegister::registerService(
            ShopPaymentService::class,
            static function () {
                return new PaymentMethodService(
                    ServiceRegister::getService(StoreContext::class),
                    Shopware()->Models(),
                    Shopware()->Container()->get(StoreRepository::class),
                    Shopware()->Container()->get('shopware.plugin_payment_installer'),
                    ServiceRegister::getService(FileService::class),
                    ServiceRegister::getService(PaymentMethodConfigRepository::class),
                    ServiceRegister::getService(StoreServiceInterface::class)
                );
            }
        );

        ServiceRegister::registerService(
            StoreServiceInterface::class,
            static function () {
                return new StoreService(
                    Shopware()->Container()->get(StoreRepository::class),
                    Shopware()->Container()->get(OrderRepository::class),
                    RepositoryRegistry::getRepository(ConnectionSettings::getClassName())
                );
            }
        );

        ServiceRegister::registerService(
            WebhookUrlService::class,
            static function () {
                return new \AdyenPayment\Components\Integration\WebhookUrlService(
                    ServiceRegister::getService(StoreContext::class)
                );
            }
        );

        // Override WebhookHandler to swap old plugin merchant reference to a new one (old order number -> new order temporary id)
        ServiceRegister::registerService(
            WebhookHandler::class,
            new SingleInstance(static function () {
                return new LegacyMerchantReferenceNormalizationWebhookHandler(
                    Shopware()->Container()->get(OrderRepository::class),
                    ServiceRegister::getService(WebhookSynchronizationServiceInterface::class),
                    ServiceRegister::getService(QueueService::class)
                );
            })
        );

        ServiceRegister::registerService(
            BaseTransactionDetailsServiceAlias::class,
            static function () {
                return new TransactionDetailsService(
                    ServiceRegister::getService(ConnectionService::class),
                    ServiceRegister::getService(TransactionHistoryService::class),
                    ServiceRegister::getService(GeneralSettingsService::class),
                    ServiceRegister::getService(OrderServiceInterface::class)
                );
            }
        );

        ServiceRegister::registerService(
            LastOpenTimeService::class,
            static function () {
                return new LastOpenTimeService(
                    RepositoryRegistry::getRepository(LastOpenTime::getClassName())
                );
            }
        );

        ServiceRegister::registerService(
            SystemInfoServiceInterface::class,
            static function () {
                return new SystemInfoService(
                    ServiceRegister::getService(Configuration::CLASS_NAME),
                    Shopware()->Container()->get(StoreRepository::class)
                );
            }
        );

        ServiceRegister::registerService(
            UninstallService::class,
            static function () {
                return new UninstallService(
                    ServiceRegister::getService(StoreServiceInterface::class)
                );
            }
        );

        ServiceRegister::registerService(
            CustomerService::class,
            static function () {
                return new CustomerService(Shopware()->Container()->get('models'));
            }
        );
    }

    public static function initPaymentRequestProcessors(): void
    {
        parent::initPaymentRequestProcessors();

        ServiceRegister::registerService(
            L2L3DataProcessor::class,
            static function () {
                /** @noinspection NullPointerExceptionInspection */
                return new IntegrationL2L3DataProcessor(
                    ServiceRegister::getService(PaymentService::class),
                    Shopware()->Container()->get('models')->getRepository(Country::class)
                );
            }
        );

        ServiceRegister::registerService(
            BasketItemsProcessor::class,
            static function () {
                /** @noinspection NullPointerExceptionInspection */
                return new \AdyenPayment\Components\Integration\PaymentProcessors\BasketItemsProcessor(
                    ServiceRegister::getService(GeneralSettingsService::class),
                    Shopware()->Container()->get('models')->getRepository(Article::class)
                );
            }
        );

        ServiceRegister::registerService(
            AddressProcessor::class,
            static function () {
                /** @noinspection NullPointerExceptionInspection */
                return new IntegrationAddressProcessor(
                    Shopware()->Container()->get('models')->getRepository(Country::class),
                    Shopware()->Container()->get(OrderRepository::class)
                );
            }
        );

        ServiceRegister::registerService(
            BirthdayProcessor::class,
            static function () {
                return new IntegrationBirthdayProcessor(Shopware()->Container()->get(OrderRepository::class));
            }
        );

        ServiceRegister::registerService(
            LineItemsProcessor::class,
            static function () {
                /** @noinspection NullPointerExceptionInspection */
                return new IntegrationLineItemsProcessor(
                    Shopware()->Container()->get('models')->getRepository(Article::class),
                    Shopware()->Container()->get(OrderRepository::class)
                );
            }
        );

        ServiceRegister::registerService(
            ShopperEmailProcessor::class,
            static function () {
                return new IntegrationShopperEmailProcessor(Shopware()->Container()->get(OrderRepository::class));
            }
        );

        ServiceRegister::registerService(
            ShopperNameProcessor::class,
            static function () {
                return new IntegrationShopperNameProcessor(Shopware()->Container()->get(OrderRepository::class));
            }
        );

        ServiceRegister::registerService(
            ShopperLocaleProcessor::class,
            static function () {
                return new IntegrationShopperLocaleProcessor();
            }
        );

        ServiceRegister::registerService(
            VersionService::class,
            static function () {
                return new \AdyenPayment\Components\Integration\VersionService();
            }
        );

        ServiceRegister::registerService(
            PaymentLinkAddressProcessorInterface::class,
            static function () {
                /** @noinspection NullPointerExceptionInspection */
                return new IntegrationAddressProcessor(
                    Shopware()->Container()->get('models')->getRepository(Country::class),
                    Shopware()->Container()->get(OrderRepository::class)
                );
            }
        );

        ServiceRegister::registerService(
            PaymentLinkLineItemsProcessorInterface::class,
            static function () {
                /** @noinspection NullPointerExceptionInspection */
                return new IntegrationLineItemsProcessor(
                    Shopware()->Container()->get('models')->getRepository(Article::class),
                    Shopware()->Container()->get(OrderRepository::class)
                );
            }
        );

        ServiceRegister::registerService(
            PaymentLinkShopperBirthdayProcessorInterface::class,
            static function () {
                return new IntegrationBirthdayProcessor(Shopware()->Container()->get(OrderRepository::class));
            }
        );

        ServiceRegister::registerService(
            PaymentLinkShopperEmailProcessorInterface::class,
            static function () {
                return new IntegrationShopperEmailProcessor(Shopware()->Container()->get(OrderRepository::class));
            }
        );

        ServiceRegister::registerService(
            PaymentLinkShopperLocaleProcessorInterface::class,
            static function () {
                return new IntegrationShopperLocaleProcessor();
            }
        );

        ServiceRegister::registerService(
            PaymentLinkShopperNameProcessorInterface::class,
            static function () {
                return new IntegrationShopperNameProcessor(Shopware()->Container()->get(OrderRepository::class));
            }
        );

        ServiceRegister::registerService(
            PaymentLinkShopperReferenceProcessorInterface::class,
            static function () {
                return new IntegrationShopperReferenceProcessor(Shopware()->Container()->get(OrderRepository::class));
            }
        );
    }

    /**
     * @inheritDoc
     *
     * @throws RepositoryClassException
     */
    protected static function initRepositories(): void
    {
        parent::initRepositories();

        RepositoryRegistry::registerRepository(Process::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(QueueItem::getClassName(), QueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(LogData::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(
            ConnectionSettings::getClassName(),
            BaseRepositoryWithConditionalDeletes::getClassName()
        );
        RepositoryRegistry::registerRepository(AdyenGivingSettings::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(GeneralSettings::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(WebhookConfig::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(PaymentMethod::getClassName(), PaymentMethodRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderStatusMapping::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(
            TransactionHistory::getClassName(),
            TransactionLogRepository::getClassName()
        );
        RepositoryRegistry::registerRepository(
            TransactionLog::getClassName(),
            TransactionLogRepository::getClassName()
        );
        RepositoryRegistry::registerRepository(DonationsData::getClassName(), AdyenGivingRepository::getClassName());
        RepositoryRegistry::registerRepository(Notification::getClassName(), NotificationsRepository::getClassName());
        RepositoryRegistry::registerRepository(LastOpenTime::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(DisconnectTime::getClassName(), BaseRepository::getClassName());
    }
}
