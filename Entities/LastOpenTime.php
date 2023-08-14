<?php

namespace AdyenPayment\Entities;

use Adyen\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Adyen\Core\Infrastructure\ORM\Configuration\IndexMap;
use Adyen\Core\Infrastructure\ORM\Entity;

class LastOpenTime extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * @var string
     */
    protected $timestamp;

    protected $fields = ['id', 'timestamp'];

    public function getConfig(): EntityConfiguration
    {
        $indexMap = new IndexMap();

        return new EntityConfiguration($indexMap, 'LastOpenTime');
    }
    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     */
    public function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
