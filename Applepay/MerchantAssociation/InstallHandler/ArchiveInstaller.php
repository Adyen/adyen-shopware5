<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation\InstallHandler;

use AdyenPayment\Applepay\Exception\ArchiveNotAccessibleException;
use AdyenPayment\Applepay\Exception\ArchiveNotExtractedException;
use AdyenPayment\Applepay\MerchantAssociation\StorageFilesystem;

final class ArchiveInstaller implements Installer
{
    private const ARCHIVED_FILE_NAME = 'apple-developer-merchantid-domain-association';
    private string $archivePath;
    private StorageFilesystem $storageFilesystem;

    public function __construct(string $archivePath, StorageFilesystem $storageFilesystem)
    {
        $this->archivePath = $archivePath;
        $this->storageFilesystem = $storageFilesystem;
    }

    public function isFallback(): bool
    {
        return true;
    }

    public function install(): void
    {
        $archive = new \ZipArchive();
        if (false === $archive->open($this->archivePath)) {
            throw ArchiveNotAccessibleException::fromPath($this->archivePath);
        }

        $this->storageFilesystem->resetStorage();
        $extracted = $archive->extractTo(
            dirname($this->storageFilesystem->storagePath()),
            [self::ARCHIVED_FILE_NAME]
        );
        if (!$extracted) {
            $archive->close();

            throw ArchiveNotExtractedException::fromPaths($this->archivePath, $this->storageFilesystem->storagePath());
        }
        $this->storageFilesystem->updateFilePermissions();
        $archive->close();
    }
}
