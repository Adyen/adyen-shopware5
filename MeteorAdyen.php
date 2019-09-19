<?php

namespace MeteorAdyen;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\KeyFactory;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Payment\Payment;


class MeteorAdyen extends Plugin
{
    const NAME = 'MeteorAdyen';
    const PAYMENT_METHOD_CARD = 'meteor_adyen_card';

    /**
     * @param InstallContext $context
     * @throws CannotPerformOperation
     * @throws InvalidKey
     */
    public function install(InstallContext $context)
    {
        $this->installPaymentMethodes($context);
        $this->generateEncryptionKey();
    }

    private function installPaymentMethodes(InstallContext $context)
    {
        /** @var PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $options = [
            'name' => self::PAYMENT_METHOD_CARD,
            'description' => 'Credit Cards',
            'action' => 'PaymentCard',
            'active' => 0,
            'position' => 0,
            'additionalDescription' =>
                '<img src="https://checkoutshopper-live.adyen.com/checkoutshopper/images/logos/card.svg"/>'
                . '<div id="payment_desc">'
                . '  Pay save and secured by invoice with our example payment provider.'
                . '</div>'
        ];
        $installer->createOrUpdate($context->getPlugin(), $options);
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

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
    }

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
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