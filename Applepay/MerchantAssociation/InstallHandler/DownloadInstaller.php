<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation\InstallHandler;

use AdyenPayment\Applepay\Exception\FileNotDownloadedException;
use AdyenPayment\Applepay\MerchantAssociation\StorageFilesystem;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

final class DownloadInstaller implements Installer
{
    /** @var ClientInterface */
    private $client;

    /** @var StorageFilesystem */
    private $storageFilesystem;

    public function __construct(Client $client, StorageFilesystem $storageFilesystem)
    {
        $this->client = $client;
        $this->storageFilesystem = $storageFilesystem;
    }

    public function isFallback(): bool
    {
        return false;
    }

    public function install(): void
    {
        try {
            $this->storageFilesystem->resetStorage();
            /** @psalm-suppress UndefinedInterfaceMethod (psalm does not recognize trait method) */
            $this->client->get('/.well-known/apple-developer-merchantid-domain-association', [
                'sink' => $this->storageFilesystem->storagePath(),
            ]);
            $this->storageFilesystem->updateFilePermissions();
        } catch (GuzzleException $exception) {
            throw FileNotDownloadedException::fromException($exception);
        }
    }
}
