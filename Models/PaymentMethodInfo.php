<?php

declare(strict_types=1);

namespace AdyenPayment\Models;

class PaymentMethodInfo
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $type;

    private function __construct(string $name, string $description, string $type)
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
    }

    public static function create(string $name, string $description, string $type): self
    {
        return new self($name, $description, $type);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
