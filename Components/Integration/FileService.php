<?php

namespace AdyenPayment\Components\Integration;

use Shopware\Bundle\MediaBundle\MediaServiceInterface;

/**
 * Class FileService
 *
 * @package AdyenPayment\BusinessService
 */
class FileService
{
    /**
     * @var MediaServiceInterface
     */
    private $mediaService;

    /**
     * @param MediaServiceInterface $mediaService
     */
    public function __construct(MediaServiceInterface $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * @param string $fileContent
     * @param string $name
     *
     * @return void
     */
    public function write(string $fileContent, string $name): void
    {
        $path = 'media/image/' . $name . '.png';

        if ($this->mediaService->has($path)) {
            $this->delete($path);
        }
        $this->mediaService->write($path, $fileContent);
    }

    /**
     * @param string $name
     *
     * @return false|string
     */
    public function read(string $name)
    {
        return $this->mediaService->read('media/image/' . $name . '.png');
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getLogoUrl(string $name): ?string
    {
        if ($this->mediaService->has('media/image/' . $name . '.png')) {
            return $this->mediaService->getUrl('media/image/' . $name . '.png');
        }

        return '';
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function delete(string $name): void
    {
        if (!$this->mediaService->has('media/image/' . $name . '.png')) {
            return;
        }

        $this->mediaService->delete('media/image/' . $name . '.png');
    }
}
