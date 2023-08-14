<?php

namespace AdyenPayment;

use Adyen\Core\BusinessLogic\DataAccess\Connection\Entities\ConnectionSettings;
use Adyen\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionSettingsRepository;
use Adyen\Core\BusinessLogic\Domain\Connection\Services\ConnectionService;
use Adyen\Core\BusinessLogic\Domain\Integration\Order\OrderService as OrderServiceInterface;
use Adyen\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Repositories\PaymentMethodConfigRepository;
use Adyen\Core\Infrastructure\ORM\RepositoryRegistry;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Core\Infrastructure\TaskExecution\QueueService;
use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService as StoreServiceInterface;
use AdyenPayment\Bootstrap\Bootstrap;
use AdyenPayment\Components\Integration\FileService;
use AdyenPayment\Components\Integration\OrderService;
use AdyenPayment\Components\Integration\PaymentMethodService;
use AdyenPayment\Components\Integration\StoreService;
use AdyenPayment\Components\UninstallService;
use AdyenPayment\Models\AdyenEntity;
use AdyenPayment\Models\NotificationsEntity;
use AdyenPayment\Models\QueueEntity;
use AdyenPayment\Models\TransactionLogEntity;
use AdyenPayment\Models\UserPreference;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use AdyenPayment\Repositories\Wrapper\StoreRepository;
use AdyenPayment\Setup\Updater;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Shopware-Plugin AdyenPayment.
 */
class AdyenPayment extends Plugin
{
    public const NAME = 'AdyenPayment';
    public const PAYMENT_METHOD_SOURCE = 1425514;
    public const STORED_PAYMENT_UMBRELLA_NAME = 'adyen_stored_payment_umbrella';

    public static function isPackage(): bool
    {
        return file_exists(static::getPackageVendorAutoload());
    }

    public static function getPackageVendorAutoload(): string
    {
        return __DIR__ . '/vendor/autoload.php';
    }

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('adyen_payment.plugin_dir', $this->getPath());

        parent::build($container);

        $this->loadServices($container);
    }

    /**
     * Adds the widget to the database and creates the database schema.
     *
     * @param Plugin\Context\InstallContext $installContext
     */
    public function install(Plugin\Context\InstallContext $installContext)
    {
        parent::install($installContext);

        $this->container->get('shopware.snippet_database_handler')->loadToDatabase(
                $this->getPath() . '/Resources/snippets/'
        );

        $this->updateSchema();
        $this->installStoredPaymentUmbrella();

        $installContext->scheduleClearCache(InstallContext::CACHE_LIST_FRONTEND);
    }

    public function update(UpdateContext $context): void
    {
        Bootstrap::init();

        $this->container->get('shopware.snippet_database_handler')->loadToDatabase(
                $this->getPath() . '/Resources/snippets/'
        );

        $this->updateSchema();

        $updater = new Updater(
            $context,
            $this->container->get('shopware.plugin.cached_config_reader'),
            ServiceRegister::getService(ConnectionService::class),
            $this->container->get(StoreRepository::class),
            ServiceRegister::getService(PaymentMethodConfigRepository::class),
            $this->container->get('snippets'),
            $this->container->get('cron'),
            ServiceRegister::getService(QueueService::class),
            ServiceRegister::getService(ConnectionSettingsRepository::class)
        );
        $updater->update();

        $this->installStoredPaymentUmbrella();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_FRONTEND);
        $this->migrateLegacySchema();

        parent::update($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = ServiceRegister::getService(ShopPaymentService::class);
        $paymentMethodService->deletePaymentMethodsForAllStores();
        $this->installStoredPaymentUmbrella(false);

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    public function activate(ActivateContext $context): void
    {
        $this->initServices();
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = ServiceRegister::getService(ShopPaymentService::class);
        $paymentMethodService->enableAllPaymentMethods();
        $this->installStoredPaymentUmbrella();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * Remove widget and remove database schema.
     *
     * @param Plugin\Context\UninstallContext $uninstallContext
     *
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function uninstall(Plugin\Context\UninstallContext $uninstallContext)
    {
        parent::uninstall($uninstallContext);
        $this->initServices();

        if ($uninstallContext->keepUserData()) {
            /** @var PaymentMethodService $paymentService */
            $paymentService = ServiceRegister::getService(ShopPaymentService::class);
            $paymentService->deletePaymentMethodsForAllStores();
            $this->installStoredPaymentUmbrella(false);

            return;
        }

        $uninstallService = new UninstallService(
            new StoreService(
                new StoreRepository(Shopware()->Models()->getRepository(Shop::class)),
                new OrderRepository(Shopware()->Models()->getRepository(Order::class)),
                RepositoryRegistry::getRepository(ConnectionSettings::getClassName())
            )
        );
        try {
            $uninstallService->uninstall();
        } catch (Exception $exception) {
            $this->container->get('corelogger')->warning($exception->getMessage());
        }

        $this->installStoredPaymentUmbrella(false);

        $this->removeSchema();
    }

    private function loadServices(ContainerBuilder $container): void
    {
        $loader = new GlobFileLoader($container, $fileLocator = new FileLocator());
        $loader->setResolver(new LoaderResolver([new XmlFileLoader($container, $fileLocator)]));

        $loader->load(__DIR__ . '/Resources/services/*.xml');

        $versionCheck = $container->get('adyen_payment.components.shopware_version_check');
        if ($versionCheck && $versionCheck->isHigherThanShopwareVersion('v5.6.2')) {
            $loader->load(__DIR__ . '/Resources/services/version/563/*.xml');
        }
    }

    /**
     * Creates/updates database tables on base of doctrine models
     */
    private function updateSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));

        $tool->updateSchema($this->getModelMetaData(), true);
    }

    private function removeSchema(): void
    {
        $tool = new SchemaTool($this->container->get('models'));

        $tool->dropSchema($this->getModelMetaData());
        $this->removeLegacySchema();
    }

    private function removeLegacySchema(): void
    {
        $sql = 'DROP TABLE IF EXISTS `s_plugin_adyen_order_notification`;
                DROP TABLE IF EXISTS `s_plugin_adyen_order_payment_info`;
                DROP TABLE IF EXISTS `s_plugin_adyen_order_refund`;
                DROP TABLE IF EXISTS `s_plugin_adyen_text_notification`;
                DROP TABLE IF EXISTS `s_plugin_adyen_payment_recurring_payment_token`;';

        $this->container->get('dbal_connection')->exec($sql);
    }

    private function migrateLegacySchema()
    {
        $sql = 'DROP TABLE IF EXISTS `s_plugin_adyen_order_payment_info`;
                DROP TABLE IF EXISTS `s_plugin_adyen_order_refund`;
                DROP TABLE IF EXISTS `s_plugin_adyen_payment_recurring_payment_token`;';

        $this->container->get('dbal_connection')->exec($sql);
    }

    private function getModelMetaData(): array
    {
        $entityManager = $this->container->get('models');

        return [
            $entityManager->getClassMetadata(AdyenEntity::class),
            $entityManager->getClassMetadata(NotificationsEntity::class),
            $entityManager->getClassMetadata(QueueEntity::class),
            $entityManager->getClassMetadata(TransactionLogEntity::class),
            $entityManager->getClassMetadata(UserPreference::class),
        ];
    }

    private function installStoredPaymentUmbrella($isActive = true): void
    {
        /** @var PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');
        $installer->createOrUpdate(
            self::NAME,
            [
                'name' => self::STORED_PAYMENT_UMBRELLA_NAME,
                'description' => 'Adyen Stored Payment Method',
                'additionalDescription' => 'Adyen Stored Payment Method',
                'active' => $isActive,
                'esdActive' => $isActive,
                'hide' => true,
                'action' => 'AdyenPaymentProcess',
                'source' => self::PAYMENT_METHOD_SOURCE,
            ]
        );
    }

    private function initServices(): void
    {
        Bootstrap::init();

        ServiceRegister::registerService(
            ShopPaymentService::class,
            static function () {
                return new PaymentMethodService(
                    ServiceRegister::getService(StoreContext::class),
                    Shopware()->Models(),
                    new StoreRepository(Shopware()->Models()->getRepository(Shop::class)),
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
                            new StoreRepository(Shopware()->Models()->getRepository(Shop::class)),
                            new OrderRepository(Shopware()->Models()->getRepository(Order::class)),
                            RepositoryRegistry::getRepository(ConnectionSettings::getClassName())
                    );
                }
        );

        ServiceRegister::registerService(
                OrderServiceInterface::class,
                static function () {
                    return new OrderService(
                            new OrderRepository(Shopware()->Models()->getRepository(Order::class)),
                            Shopware()->Modules()
                    );
                }
        );
    }
}

if (AdyenPayment::isPackage()) {
    require_once AdyenPayment::getPackageVendorAutoload();
}
