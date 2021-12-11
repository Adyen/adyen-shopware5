<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Shopware\Plugin\TraceablePluginIdProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentFactory implements PaymentFactoryInterface
{
    private const ADYEN_PREFIX = 'Adyen';
    private ModelRepository $countryRepository;
    private TraceablePluginIdProvider $pluginIdProvider;

    public function __construct(ModelRepository $countryRepository, TraceablePluginIdProvider $pluginIdProvider)
    {
        $this->countryRepository = $countryRepository;
        $this->pluginIdProvider = $pluginIdProvider;
    }

    public function createFromAdyen(PaymentMethod $paymentMethod, Shop $shop): Payment
    {
        $new = new Payment();
        $new->setActive(true);
        $new->setName($paymentMethod->code());
        $new->setDescription($paymentMethod->name());
        $new->setAdditionalDescription($this->provideAdditionalDescription($paymentMethod));
        $new->setShops(new ArrayCollection([$shop]));
        $new->setSource(SourceType::adyen()->getType());
        $new->setPluginId($this->pluginIdProvider->provideId());
        $new->setCountries(new ArrayCollection(
            $this->countryRepository->findAll()
        ));

        return $new;
    }

    public function updateFromAdyen(Payment $payment, PaymentMethod $paymentMethod, Shop $shop): Payment
    {
        $payment->setName($paymentMethod->code());
        $payment->setDescription($paymentMethod->name());
        $payment->setAdditionalDescription($this->provideAdditionalDescription($paymentMethod));
        $payment->setShops(new ArrayCollection([$shop]));
        $payment->setSource(SourceType::adyen()->getType());
        $payment->setPluginId($this->pluginIdProvider->provideId());
        $payment->setCountries(new ArrayCollection(
            $this->countryRepository->findAll()
        ));

        return $payment;
    }

    private function provideAdditionalDescription(PaymentMethod $paymentMethod): string
    {
        return self::ADYEN_PREFIX.' '.$paymentMethod->name().' ('.$paymentMethod->adyenType()->type().')';
    }
}
