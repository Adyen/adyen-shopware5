<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation;

use Symfony\Component\Filesystem\Filesystem;

final class StorageFilesystem
{
    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $storagePath;

    public function __construct(Filesystem $filesystem, string $storagePath)
    {
        $this->filesystem = $filesystem;
        $this->storagePath = $storagePath;
    }

    public function storageFileExists(): bool
    {
        return $this->filesystem->exists($this->storagePath);
    }

    public function storagePath(): string
    {
        return $this->storagePath;
    }

    public function resetStorage(): void
    {
        $this->createDirectory();
        $this->removeFile();
    }

    public function updateFilePermissions(): void
    {
        if (!$this->filesystem->exists($this->storagePath)) {
            return;
        }

        $this->filesystem->chmod($this->storagePath, 0664);
    }

    public function createDirectory(): void
    {
        $directory = dirname($this->storagePath);
        if ($this->filesystem->exists($directory)) {
            return;
        }

        $this->filesystem->mkdir($directory);
        $this->filesystem->chmod($directory, 0764);
    }

    private function removeFile(): void
    {
        if (!$this->filesystem->exists($this->storagePath)) {
            return;
        }

        $this->filesystem->remove($this->storagePath);
    }
}
