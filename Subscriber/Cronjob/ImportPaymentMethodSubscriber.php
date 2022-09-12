<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Cronjob;

use AdyenPayment\Import\PaymentMethodImporterInterface;
use AdyenPayment\Models\Event;
use Enlight\Event\SubscriberInterface;

final class ImportPaymentMethodSubscriber implements SubscriberInterface
{
    /** @var PaymentMethodImporterInterface */
    private $paymentMethodImporter;

    public function __construct(PaymentMethodImporterInterface $paymentMethodImporter)
    {
        $this->paymentMethodImporter = $paymentMethodImporter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Event::cronImportPaymentMethods()->getName() => '__invoke',
        ];
    }

    public function __invoke(\Shopware_Components_Cron_CronJob $job): void
    {
        iterator_to_array($this->paymentMethodImporter->importAll());
    }
}
