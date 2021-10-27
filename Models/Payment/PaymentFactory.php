<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\PluginType;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

class PaymentFactory implements PaymentFactoryInterface
{
    private const ADYEN_PREFIX = 'Adyen';

    private ModelRepository $countryRepository;

    public function __construct($countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function createFromAdyen(PaymentMethod $adyenPaymentMethod, Shop $shop): Payment
    {
        $name = $adyenPaymentMethod->getValue('name');

        $new = new Payment();
        $new->setActive(true);
        // @TODO: sanitize $name, prevent: "adyen_GiftCard Givex"
        $new->setName($adyenPaymentMethod->getType().'_'.$name);
        $new->setDescription($name);
        $new->setAdditionalDescription(self::ADYEN_PREFIX." ".$name);
        $new->setShops(new ArrayCollection([$shop]));
        $new->setSource(SourceType::adyen()->getType());
        $new->setPluginId(PluginType::adyenType()->getType());
        $new->setCountries(new ArrayCollection(
            $this->countryRepository->findAll()
        ));

        return $new;
    }

    public function updateFromAdyen(Payment $payment, PaymentMethod $adyenPaymentMethod, Shop $shop): Payment
    {
        $name = $adyenPaymentMethod->getValue('name');

        $payment->setName($name);
        $payment->setDescription($name);
        $payment->setAdditionalDescription(self::ADYEN_PREFIX." ".$name);
        $payment->setShops(new ArrayCollection([$shop]));
        $payment->setSource(SourceType::adyen()->getType());
        $payment->setPluginId(PluginType::adyenType()->getType());
        $payment->setCountries(new ArrayCollection(
            $this->countryRepository->findAll()
        ));

        return $payment;
    }
}
