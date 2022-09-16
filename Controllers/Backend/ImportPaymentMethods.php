<?php

declare(strict_types=1);

use AdyenPayment\Import\PaymentMethodImporter;
use AdyenPayment\Import\PaymentMethodImporterInterface;
use Psr\Log\LoggerInterface;
use Shopware\Components\CacheManager;
use Symfony\Component\HttpFoundation\Response;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
class Shopware_Controllers_Backend_ImportPaymentMethods extends Shopware_Controllers_Backend_ExtJs
{
    /** @var PaymentMethodImporterInterface */
    private $paymentMethodImporter;

    /** @var LoggerInterface */
    private $logger;

    /** @var CacheManager */
    private $cacheManager;

    /**
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->cacheManager = $this->get('shopware.cache_manager');
        $this->paymentMethodImporter = $this->get(PaymentMethodImporter::class);
        $this->logger = $this->get('adyen_payment.logger');
    }

    public function importAction(): void
    {
        try {
            $this->cacheManager->clearConfigCache();

            $total = $success = 0;
            foreach ($this->paymentMethodImporter->importAll() as $result) {
                ++$total;
                if ($result->isSuccess()) {
                    ++$success;
                }
            }

            $this->response->setHttpResponseCode(Response::HTTP_OK);
            $this->View()->assign('responseText', sprintf('Imported %s of %s payment method(s).%s',
                $success,
                $total,
                $total !== $success ? ' Details can be found in adyen log.' : ''
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->View()->assign('responseText',
                sprintf('Import of payment methods failed. Please check the logs for more details.')
            );
        }
    }
}
