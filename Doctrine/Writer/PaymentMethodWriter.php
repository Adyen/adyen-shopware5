<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Dbal\Provider\Payment\PaymentMeanProviderInterface;
use AdyenPayment\Models\Payment\PaymentFactoryInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentMethodWriter implements PaymentMethodWriterInterface
{
    /** @var ModelManager */
    private $entityManager;
    /** @var PaymentMeanProviderInterface */
    private $paymentMeanProvider;
    /** @var PaymentFactoryInterface */
    private $paymentFactory;
    /** @var PaymentAttributesWriterInterface */
    private $paymentAttributesWriter;

    public function __construct(
        ModelManager $entityManager,
        PaymentMeanProviderInterface $paymentMeanProvider,
        PaymentFactoryInterface $paymentFactory,
        PaymentAttributesWriterInterface $paymentAttributesWriter
    ) {
        $this->entityManager = $entityManager;
        $this->paymentMeanProvider = $paymentMeanProvider;
        $this->paymentFactory = $paymentFactory;
        $this->paymentAttributesWriter = $paymentAttributesWriter;
    }

    public function __invoke(
        PaymentMethod $adyenPaymentMethod,
        Shop $shop
    ): ImportResult {
        $payment = $this->write($adyenPaymentMethod, $shop);

        $this->paymentAttributesWriter->storeAdyenPaymentMethodType(
            $payment->getId(),
            $adyenPaymentMethod
        );

        return ImportResult::success($shop, $adyenPaymentMethod);
    }

    private function write(PaymentMethod $adyenPaymentMethod, Shop $shop): Payment
    {
        $swPayment = $this->paymentMeanProvider->provideByAdyenType($adyenPaymentMethod->getType());

        $payment = null !== $swPayment
            ? $this->paymentFactory->updateFromAdyen($swPayment, $adyenPaymentMethod, $shop)
            : $this->paymentFactory->createFromAdyen($adyenPaymentMethod, $shop);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
