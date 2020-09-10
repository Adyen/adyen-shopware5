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
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Logger;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\PaymentInstaller;
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
    const ADYEN_GENERAL_PAYMENT_METHOD = 'adyen_general_payment_method';
    const ADYEN_PAYMENT_PAYMENT_METHOD = 'adyen_payment_payment_method';

    const SESSION_ADYEN_PAYMENT = 'adyenPayment';
    const SESSION_ADYEN_PAYMENT_VALID = 'adyenPaymentValid';
    const SESSION_ADYEN_PAYMENT_DATA = 'adyenPaymentData';

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

    /**
     * @param UninstallContext $context
     * @throws Exception
     */
    public function uninstall(UninstallContext $context)
    {
        $this->deactivatePaymentMethods();

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
        $this->deactivatePaymentMethods();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        /** @var PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $paymentOptions[] = $this->getPaymentOptions();

        foreach ($paymentOptions as $key => $options) {
            $installer->createOrUpdate($context->getPlugin(), $options);
        }

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * Deactivate all Adyen payment methods
     */
    private function deactivatePaymentMethods()
    {
        $em = $this->container->get('models');
        $qb = $em->createQueryBuilder();

        $query = $qb->update('Shopware\Models\Payment\Payment', 'p')
            ->set('p.active', '?1')
            ->where($qb->expr()->like('p.name', '?2'))
            ->setParameter(1, false)
            ->setParameter(2, self::ADYEN_GENERAL_PAYMENT_METHOD)
            ->getQuery();

        $query->execute();
    }

    /**
     * @param UninstallContext $uninstallContext
     * @throws Exception
     */
    private function uninstallAttributes(UninstallContext $uninstallContext)
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->delete('s_user_attributes', self::ADYEN_PAYMENT_PAYMENT_METHOD);

        $this->rebuildAttributeModels();
    }

    /**
     * @throws Exception
     */
    private function installAttributes()
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->update(
            's_user_attributes',
            self::ADYEN_PAYMENT_PAYMENT_METHOD,
            TypeMapping::TYPE_STRING,
            [
                'displayInBackend' => true,
                'label' => 'Adyen Payment Method'
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
        ];
    }

    /**
     * @return array
     */
    private function getPaymentOptions()
    {
        $options = [
            'name' => self::ADYEN_GENERAL_PAYMENT_METHOD,
            'description' => 'Adyen payment methods',
            'action' => null,
            'active' => 1,
            'position' => 0,
            'additionalDescription' => ''
        ];

        return $options;
    }

    private function rebuildAttributeModels()
    {
        /** @var Cache $metaDataCache */
        $metaDataCache = $this->container->get('models')->getConfiguration()->getMetadataCacheImpl();
        if ($metaDataCache) {
            $metaDataCache->deleteAll();
        }

        $this->container->get('models')->generateAttributeModels(['s_user_attributes']);
    }
}

if (AdyenPayment::isPackage()) {
    require_once AdyenPayment::getPackageVendorAutoload();
}
//phpcs:enable PSR1.Files.SideEffects
