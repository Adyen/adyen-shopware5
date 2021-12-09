<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Crud;

interface AttributeWriterInterface
{
    /**
     * @param array<string> $columns
     */
    public function writeReadOnlyAttributes(string $attributeTable, array $columns, callable $writer): void;
}
