<?php

declare(strict_types=1);

use AdyenPayment\Applepay\Certificate\Filesystem\CertificateReader;
use AdyenPayment\Applepay\Certificate\Filesystem\CertificateReaderInterface;
use AdyenPayment\Applepay\MerchantAssociation\AssociationFileInstaller;
use AdyenPayment\Applepay\MerchantAssociation\MerchantAssociationFileInstaller;
use AdyenPayment\Applepay\MerchantAssociation\StorageFilesystem;
use AdyenPayment\Utils\JsonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Frontend_ApplePayMerchantAssociation extends Enlight_Controller_Action
{
    /** @var StorageFilesystem */
    private $storageFilesystem;

    /** @var AssociationFileInstaller */
    private $merchantAssociationFileInstaller;

    /** @var LoggerInterface */
    private $logger;

    public function preDispatch(): void
    {
        $this->storageFilesystem = $this->get(StorageFilesystem::class);
        $this->merchantAssociationFileInstaller = $this->get(MerchantAssociationFileInstaller::class);
        $this->logger = $this->get('adyen_payment.logger');
    }

    public function indexAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        if (!$this->storageFilesystem->storageFileExists()) {
            iterator_to_array(($this->merchantAssociationFileInstaller)());
        }

        if (!$this->storageFilesystem->storageFileExists()) {
            $this->logger->critical($message = 'Could not serve Adyen ApplePay merchant id association file.');
            $this->Response()->setHeader('Content-Type', 'application/json');
            $this->Response()->setHttpResponseCode(Response::HTTP_FAILED_DEPENDENCY);
            $this->Response()->setBody(JsonUtil::encode([
                'success' => false,
                'details' => $message,
            ]));

            return;
        }

        $this->Response()->setHeader('Content-Type', 'text/plain');
        $this->Response()->setBody(
            file_get_contents($this->storageFilesystem->storagePath())
        );
    }
}
