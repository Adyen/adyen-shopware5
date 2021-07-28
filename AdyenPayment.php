<?php

//phpcs:disable PSR1.Files.SideEffects

namespace AdyenPayment;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use AdyenPayment\Components\CompilerPass\NotificationProcessorCompilerPass;
use AdyenPayment\Models\Notification;
use AdyenPayment\Models\PaymentInfo;
use AdyenPayment\Models\Refund;
use AdyenPayment\Models\TextNotification;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Logger;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Class AdyenPayment
 * @package AdyenPayment
 */
class AdyenPayment extends Plugin
{
    const NAME = 'AdyenPayment';
    const ADYEN_PAYMENT_METHOD_LABEL = 'adyen_type';
    const ADYEN_PAYMENT_STORED_METHOD_ID = 'adyen_stored_method_id';

    const SESSION_ADYEN_PAYMENT_VALID = 'adyenPaymentValid'; // TODO remove
    const SESSION_ADYEN_RESTRICT_EMAILS = 'adyenRestrictEmail';
    const SESSION_ADYEN_PAYMENT_INFO_ID = 'adyenPaymentInfoId';

    /**
     * @return bool
     */
    public static function isPackage(): bool
    {
        return file_exists(static::getPackageVendorAutoload());
    }

    /**
     * @return string
     */
    public static function getPackageVendorAutoload(): string
    {
        return __DIR__ . '/vendor/autoload.php';
    }

    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new NotificationProcessorCompilerPass());

        parent::build($container);

        //set default logger level for 5.4
        if (!$container->hasParameter('kernel.default_error_level')) {
            $container->setParameter('kernel.default_error_level', Logger::ERROR);
        }

        $loader = new XmlFileLoader(
            $container,
            new FileLocator()
        );

        $loader->load(__DIR__ . '/Resources/services.xml');

        $versionCheck = $container->get('adyen_payment.components.shopware_version_check');

        if ($versionCheck->isHigherThanShopwareVersion('v5.6.2')) {
            $loader->load(__DIR__ . '/Resources/services/version/563.xml');
        }
    }

    /**
     * @param InstallContext $context
     * @throws Exception
     */
    public function install(InstallContext $context)
    {
        $this->installAttributes();

        $tool = new SchemaTool($this->container->get('models'));
        $classes = $this->getModelMetaData();
        $tool->updateSchema($classes, true);
    }

    public function update(UpdateContext $context)
    {
        $this->installAttributes();

        $tool = new SchemaTool($this->container->get('models'));
        $classes = $this->getModelMetaData();
        $tool->updateSchema($classes, true);

        parent::update($context);
    }

    /**
     * @param UninstallContext $context
     * @throws Exception
     */
    public function uninstall(UninstallContext $context)
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


    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * @param UninstallContext $uninstallContext
     * @throws Exception
     */
    private function uninstallAttributes(UninstallContext $uninstallContext)
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->delete('s_core_paymentmeans_attributes', self::ADYEN_PAYMENT_METHOD_LABEL);
        $crudService->delete('s_core_paymentmeans_attributes', self::ADYEN_PAYMENT_STORED_METHOD_ID);

        $this->rebuildAttributeModels();
    }

    /**
     * @throws Exception
     */
    private function installAttributes()
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->update(
            's_core_paymentmeans_attributes',
            self::ADYEN_PAYMENT_METHOD_LABEL,
            TypeMapping::TYPE_STRING,
            [
                'displayInBackend' => true,
                'readonly' => true,
                'label' => 'Adyen payment type'
            ]
        );
        $crudService->update(
            's_core_paymentmeans_attributes',
            self::ADYEN_PAYMENT_STORED_METHOD_ID,
            TypeMapping::TYPE_STRING,
            [
                'displayInBackend' => true,
                'readonly' => true,
                'label' => 'Adyen stored payment method id'
            ]
        );

        $this->rebuildAttributeModels();
    }

    /**
     * @return array
     */
    private function getModelMetaData(): array
    {
        $entityManager = $this->container->get('models');

        return [
            $entityManager->getClassMetadata(Notification::class),
            $entityManager->getClassMetadata(PaymentInfo::class),
            $entityManager->getClassMetadata(Refund::class),
            $entityManager->getClassMetadata(TextNotification::class)
        ];
    }

    private function rebuildAttributeModels()
    {
        /** @var Cache $metaDataCache */
        $metaDataCache = $this->container->get('models')->getConfiguration()->getMetadataCacheImpl();
        if ($metaDataCache) {
            $metaDataCache->deleteAll();
        }

        $this->container->get('models')->generateAttributeModels(['s_user_attributes', 's_core_paymentmeans_attributes']);
    }
}

if (AdyenPayment::isPackage()) {
    require_once AdyenPayment::getPackageVendorAutoload();
}
//phpcs:enable PSR1.Files.SideEffects
