<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Dbal\Provider\Payment\PaymentMeanProviderInterface;
use AdyenPayment\Exceptions\ImportPaymentMethodException;
use AdyenPayment\Models\Enum\PaymentMethod\ImportStatus;
use AdyenPayment\Models\Payment\PaymentFactoryInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentMethodWriter implements PaymentMethodWriterInterface
{
    const GIFTCARD = 'giftcard';

    /** @var ModelManager */
    private $entityManager;
    /** @var PaymentMeanProviderInterface */
    private $paymentMeanProvider;
    /** @var PaymentFactoryInterface */
    private $paymentFactory;
    /** @var PaymentAttributeWriterInterface */
    private $paymentAttributeWriter;
    /** @var StoredPaymentMethodWriterInterface */
    private $storedPaymentMethodWriter;

    public function __construct(
        ModelManager $entityManager,
        PaymentMeanProviderInterface $paymentMeanProvider,
        PaymentFactoryInterface $paymentFactory,
        PaymentAttributeWriterInterface $paymentAttributeWriter,
        StoredPaymentMethodWriterInterface $storedPaymentMethodWriter
    ) {
        $this->entityManager = $entityManager;
        $this->paymentMeanProvider = $paymentMeanProvider;
        $this->paymentFactory = $paymentFactory;
        $this->paymentAttributeWriter = $paymentAttributeWriter;
        $this->storedPaymentMethodWriter = $storedPaymentMethodWriter;
    }

    public function __invoke(
        PaymentMethod $adyenPaymentMethod,
        Shop $shop
    ): ImportResult {
        if ($adyenPaymentMethod->isStoredPayment()) {
            return $this->storedPaymentMethodWriter->__invoke($adyenPaymentMethod, $shop);
        }

        $payment = $this->write($adyenPaymentMethod, $shop);

        if (null === $payment->getId()) {
            return ImportResult::fromException(
                $shop,
                $adyenPaymentMethod,
                (new ImportPaymentMethodException)->missingId($adyenPaymentMethod, $shop)
            );
        }

        $this->paymentAttributeWriter->storeAdyenPaymentMethodType(
            $payment->getId(),
            $adyenPaymentMethod
        );

        return ImportResult::success($shop, $adyenPaymentMethod, ImportStatus::created());
    }

    private function write(PaymentMethod $adyenPaymentMethod, Shop $shop): Payment
    {
        $swPayment = $this->paymentMeanProvider->provideByAdyenType($adyenPaymentMethod->getType());

        $payment = !is_null($swPayment) && (self::GIFTCARD !== $adyenPaymentMethod->getType())
            ? $this->paymentFactory->updateFromAdyen($swPayment, $adyenPaymentMethod, $shop)
            : $this->paymentFactory->createFromAdyen($adyenPaymentMethod, $shop);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
