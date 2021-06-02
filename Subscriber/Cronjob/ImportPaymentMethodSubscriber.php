<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Cronjob;

use AdyenPayment\Import\PaymentMethodImporterInterface;
use Enlight\Event\SubscriberInterface;

class ImportPaymentMethodSubscriber implements SubscriberInterface
{
    /**
     * @var PaymentMethodImporterInterface
     */
    private $paymentMethodImporter;

    public function __construct(PaymentMethodImporterInterface $paymentMethodImporter)
    {
        $this->paymentMethodImporter = $paymentMethodImporter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'AdyenPayment_CronJob_ImportPaymentMethods' => '__invoke'
        ];
    }

    public function __invoke(\Shopware_Components_Cron_CronJob $job)
    {
        ($this->paymentMethodImporter)();
    }
}
