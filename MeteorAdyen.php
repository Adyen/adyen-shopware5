<?php

namespace MeteorAdyen;

use Doctrine\ORM\Tools\SchemaTool;
use MeteorAdyen\Models\Notification;
use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\KeyFactory;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\PaymentInstaller;


class MeteorAdyen extends Plugin
{
    const NAME = 'MeteorAdyen';
    const ADYEN_GENERAL_PAYMENT_METHOD = 'adyen_general_payment_method';

    /**
     * @param InstallContext $context
     * @throws CannotPerformOperation
     * @throws InvalidKey
     */
    public function install(InstallContext $context)
    {
        $this->generateEncryptionKey();
        $this->createFreeTextFields();
        $this->createNotificationModel();
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->deactivatePaymentMethods();
        $this->removeFreeTextFields($context);
        $this->removeNotificationModel($context);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->deactivatePaymentMethods();
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
     * Generate EncryptionKey to encrypt sensitive data in database
     *
     * @throws CannotPerformOperation
     * @throws InvalidKey
     */
    private function generateEncryptionKey()
    {
        $enc_key = KeyFactory::generateEncryptionKey();
        KeyFactory::save($enc_key, $this->getPath() . '/encryption.key');
    }

    private function createFreeTextFields()
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->update(
            's_user_attributes',
            'meteor_adyen_payment_method',
            TypeMapping::TYPE_STRING,
            [
                'displayInBackend' => true,
                'label' => 'Adyen Payment Method'
            ]
        );
    }

    private function removeFreeTextFields(UninstallContext $uninstallContext)
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $crudService = $this->container->get('shopware_attribute.crud_service');
        $crudService->delete('s_user_attributes', 'meteor_adyen_payment_method');
    }

    private function createNotificationModel()
    {
        $entityManager = $this->container->get('models');
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema(
            [
                $entityManager->getClassMetadata(Notification::class)

            ],
            true);
    }

    /**
     * @param UninstallContext $uninstallContext
     */
    private function removeNotificationModel(UninstallContext $uninstallContext)
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $entityManager = $this->container->get('models');
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema([$entityManager->getClassMetadata(Notification::class)]);
    }

    /**
     * @return bool
     */
    public static function isPackage(): bool
    {
        return file_exists(self::getPackageVendorAutoload());
    }

    /**
     * @return string
     */
    public static function getPackageVendorAutoload(): string
    {
        return __DIR__ . '/vendor/autoload.php';
    }
}

if (MeteorAdyen::isPackage()) {
    require_once MeteorAdyen::getPackageVendorAutoload();
}