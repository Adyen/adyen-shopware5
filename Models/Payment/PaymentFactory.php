<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\PluginType;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Utils\Sanitize;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentFactory implements PaymentFactoryInterface
{
    private const ADYEN_PREFIX = 'Adyen';
    private ModelRepository $countryRepository;

    public function __construct($countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function createFromAdyen(PaymentMethod $paymentMethod, Shop $shop): Payment
    {
        $new = new Payment();
        $new->setActive(true);
        $new->setName($this->provideUniqueName($paymentMethod));
        $new->setDescription($paymentMethod->getValue('name', ''));
        $new->setAdditionalDescription($this->provideAdditionalDescription($paymentMethod));
        $new->setShops(new ArrayCollection([$shop]));
        $new->setSource(SourceType::adyen()->getType());
        $new->setPluginId(PluginType::adyenType()->getType());
        $new->setCountries(new ArrayCollection(
            $this->countryRepository->findAll()
        ));

        return $new;
    }

    public function updateFromAdyen(Payment $payment, PaymentMethod $paymentMethod, Shop $shop): Payment
    {
        $payment->setName($this->provideUniqueName($paymentMethod));
        $payment->setDescription($paymentMethod->getValue('name', ''));
        $payment->setAdditionalDescription($this->provideAdditionalDescription($paymentMethod));
        $payment->setShops(new ArrayCollection([$shop])); // @todo seems on update it overwrites the exisiting one
        $payment->setSource(SourceType::adyen()->getType());
        $payment->setPluginId(PluginType::adyenType()->getType());
        $payment->setCountries(new ArrayCollection(
            $this->countryRepository->findAll()
        ));

        return $payment;
    }

    /**
     * unique name.
     */
    private function provideUniqueName(PaymentMethod $paymentMethod): string
    {
        return sprintf('%s_%s',
            $paymentMethod->getType(),
            Sanitize::removeNonWord($paymentMethod->getValue('name', ''))
        );
    }

    private function provideAdditionalDescription(PaymentMethod $paymentMethod): string
    {
        return self::ADYEN_PREFIX.' '.$paymentMethod->getValue('name', '').' ('.$paymentMethod->getType().')';
    }
}
