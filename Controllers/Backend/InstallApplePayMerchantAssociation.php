<?php

declare(strict_types=1);

use AdyenPayment\Applepay\MerchantAssociation\AssociationFileInstaller;
use AdyenPayment\Applepay\MerchantAssociation\MerchantAssociationFileInstaller;
use AdyenPayment\Applepay\MerchantAssociation\Model\InstallResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_InstallApplePayMerchantAssociation extends Shopware_Controllers_Backend_ExtJs
{
    private AssociationFileInstaller $associationFileInstaller;
    private LoggerInterface $logger;

    public function preDispatch(): void
    {
        parent::preDispatch();

        $this->associationFileInstaller = $this->container->get(MerchantAssociationFileInstaller::class);
        $this->logger = $this->get('adyen_payment.logger');
    }

    public function installAction(): void
    {
        try {
            $installResults = iterator_to_array(($this->associationFileInstaller)());
            /** @var InstallResult $finalResult */
            $finalResult = array_pop($installResults);

            if ($finalResult->success()) {
                $this->response->setHttpResponseCode(Response::HTTP_OK);
                $this->View()->assign('responseText', sprintf(
                    'Installed Adyen ApplePay Merchant Association file successfully.%s',
                    $finalResult->fallback() ? ' (used fallback)' : ''
                ));

                return;
            }

            $this->response->setHttpResponseCode(Response::HTTP_SERVICE_UNAVAILABLE);
            $this->View()->assign(
                'responseText',
                "Could not install Adyen's ApplePay Merchant Association file (see logs for details)."
            );

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            $this->response->setHttpResponseCode(Response::HTTP_SERVICE_UNAVAILABLE);
            $this->View()->assign(
                'responseText',
                "Could not download Adyen's ApplePay Merchant Association file (see logs for details)."
            );
        }
    }
}
