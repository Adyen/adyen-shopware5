<?php

declare(strict_types=1);

//phpcs:disable PSR1.Files.SideEffects

namespace AdyenPayment;

use AdyenPayment\Components\CompilerPass\NotificationProcessorCompilerPass;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Notification;
use AdyenPayment\Models\PaymentInfo;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use AdyenPayment\Models\Refund;
use AdyenPayment\Models\TextNotification;
use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class AdyenPayment extends Plugin
{
    public const NAME = 'AdyenPayment';
    public const ADYEN_CODE = 'adyen_type';
    public const ADYEN_STORED_PAYMENT_UMBRELLA_CODE = 'adyen_stored_payment_umbrella';
    public const SESSION_ADYEN_RESTRICT_EMAILS = 'adyenRestrictEmail';
    public const SESSION_ADYEN_PAYMENT_INFO_ID = 'adyenPaymentInfoId';
    public const SESSION_ADYEN_STORED_METHOD_ID = 'adyenStoredMethodId';

    public static function isPackage(): bool
    {
        return file_exists(static::getPackageVendorAutoload());
    }

    public static function getPackageVendorAutoload(): string
    {
        return __DIR__.'/vendor/autoload.php';
    }

    /**
     * @throws \Exception
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new NotificationProcessorCompilerPass());
        parent::build($container);

        //set default logger level for 5.4
        if (!$container->hasParameter('kernel.default_error_level')) {
            $container->setParameter('kernel.default_error_level', Logger::ERROR);
        }

        $this->loadServices($container);
    }

    private function loadServices(ContainerBuilder $container): void
    {
        $loader = new GlobFileLoader($container, $fileLocator = new FileLocator());
        $loader->setResolver(new LoaderResolver([new XmlFileLoader($container, $fileLocator)]));

        $serviceMainFile = __DIR__.'/Resources/services.xml';
        if (is_file($serviceMainFile)) {
            $loader->load($serviceMainFile);
        }
        $loader->load(__DIR__.'/Resources/services/*.xml');

        $versionCheck = $container->get('adyen_payment.components.shopware_version_check');
        if ($versionCheck && $versionCheck->isHigherThanShopwareVersion('v5.6.2')) {
            $loader->load(__DIR__.'/Resources/services/version/563.xml');
        }
    }

    /**
     * @throws \Exception
     */
    public function install(InstallContext $context): void
    {
        $this->installAttributes();
        $this->installStoredPaymentUmbrella($context);

        $tool = new SchemaTool($this->container->get('models'));
        $classes = $this->getModelMetaData();
        $tool->updateSchema($classes, true);

        $context->scheduleClearCache(InstallContext::CACHE_LIST_FRONTEND);
    }

    public function update(UpdateContext $context): void
    {
        $this->installAttributes();
        $this->installStoredPaymentUmbrella($context);

        $tool = new SchemaTool($this->container->get('models'));
        $classes = $this->getModelMetaData();
        $tool->updateSchema($classes, true);

        $context->scheduleClearCache(InstallContext::CACHE_LIST_FRONTEND);
        parent::update($context);
    }

    /**
     * @throws \Exception
     */
    public function uninstall(UninstallContext $context): void
    {
        if (!$context->keepUserData()) {
            $this->uninstallAttributes($context);

            $tool = new SchemaTool($this->container->get('models'));
            $classes = $this->getModelMetaData();
            $tool->dropSchema($classes);
        }

        if ($context->getPlugin()->getActive()) {
            $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        }
    }

    public function deactivate(DeactivateContext $context): void
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * @throws \Exception
     */
    private function uninstallAttributes(UninstallContext $uninstallContext): void
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->delete('s_core_paymentmeans_attributes', self::ADYEN_CODE);

        $this->rebuildAttributeModels();
    }

    /**
     * @throws \Exception
     */
    private function installAttributes(): void
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->update(
            's_core_paymentmeans_attributes',
            self::ADYEN_CODE,
            TypeMapping::TYPE_STRING,
            [
                'displayInBackend' => true,
                'readonly' => true,
                'label' => 'Adyen payment type',
            ]
        );

        $this->rebuildAttributeModels();
    }

    private function getModelMetaData(): array
    {
        $entityManager = $this->container->get('models');

        return [
            $entityManager->getClassMetadata(Notification::class),
            $entityManager->getClassMetadata(PaymentInfo::class),
            $entityManager->getClassMetadata(Refund::class),
            $entityManager->getClassMetadata(TextNotification::class),
            $entityManager->getClassMetadata(UserPreference::class),
            $entityManager->getClassMetadata(RecurringPaymentToken::class),
        ];
    }

    private function rebuildAttributeModels(): void
    {
        $metaDataCache = $this->container->get('models')->getConfiguration()->getMetadataCache();
        if ($metaDataCache) {
            $metaDataCache->clear();
        }

        $this->container->get('models')->generateAttributeModels(
            ['s_user_attributes', 's_core_paymentmeans_attributes']
        );
    }

    private function installStoredPaymentUmbrella(InstallContext $context): void
    {
        $database = $this->container->get('db');
        /** @var ModelManager $modelsManager */
        $modelsManager = $this->container->get(ModelManager::class);

        $models = $this->container->get('models');
        $shops = $models->getRepository(Shop::class)->findAll();

        $payment = new Payment();
        $payment->setActive(true);
        $payment->setName(self::ADYEN_STORED_PAYMENT_UMBRELLA_CODE);
        $payment->setSource(SourceType::adyen()->getType());
        $payment->setHide(true);
        $payment->setPluginId($context->getPlugin()->getId());
        $payment->setDescription($description = 'Adyen Stored Payment Method');
        $payment->setAdditionalDescription($description);
        $payment->setShops($shops);

        $paymentId = $database->fetchRow(
            'SELECT `id` FROM `s_core_paymentmeans` WHERE `name` = :name',
            [':name' => self::ADYEN_STORED_PAYMENT_UMBRELLA_CODE]
            )['id'] ?? null;

        if (null === $paymentId) {
            $modelsManager->persist($payment);
            $modelsManager->flush($payment);
        }
    }
}

if (AdyenPayment::isPackage()) {
    require_once AdyenPayment::getPackageVendorAutoload();
}
//phpcs:enable PSR1.Files.SideEffects
