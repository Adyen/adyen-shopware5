<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation\InstallHandler;

use AdyenPayment\Applepay\Exception\FileNotDownloadedException;
use AdyenPayment\Applepay\MerchantAssociation\StorageFilesystem;
use Shopware\Components\HttpClient\HttpClientInterface;
use Shopware\Components\HttpClient\RequestException;
use Psr\Log\LoggerInterface;

final class DownloadInstaller implements Installer
{
    /** @var string */
    private $baseUri;

    /** @var HttpClientInterface */
    private $client;

    /** @var StorageFilesystem */
    private $storageFilesystem;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        HttpClientInterface $client,
        StorageFilesystem $storageFilesystem,
        LoggerInterface $logger,
        $baseUri
    )
    {
        $this->client = $client;
        $this->storageFilesystem = $storageFilesystem;
        $this->logger = $logger;
        $this->baseUri = $baseUri;
    }

    public function isFallback(): bool
    {
        return false;
    }

    public function install(): void
    {
        try {
            $this->storageFilesystem->resetStorage();

            $url = $this->baseUri . '/.well-known/apple-developer-merchantid-domain-association';

            $this->logger->info("Sending request:\n GET $url");
            $response = $this->client->get($url);

            $this->logger->info(
                "Received response:\n".$response->getStatusCode(),
                ['response' => $response]
            );

            if ((int)$response->getStatusCode() > 400) {
                $this->logger->error('Error completing request', ['response' => $response]);
                throw FileNotDownloadedException::fromResponse($response);
            }

            $this->storageFilesystem->createFile($this->storageFilesystem->storagePath(), $response->getBody());
            $this->storageFilesystem->updateFilePermissions();
        } catch (RequestException $exception) {
            throw FileNotDownloadedException::fromException($exception);
        }
    }
}
