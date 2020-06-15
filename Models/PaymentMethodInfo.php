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
     * PaymentMethodInfo constructor.
     * @param string $name
     * @param string $description
     */
    public function __construct()
    {
        $this->name = '';
        $this->description = '';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PaymentMethodInfo
     */
    public function setName(string $name): PaymentMethodInfo
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return PaymentMethodInfo
     */
    public function setDescription(string $description): PaymentMethodInfo
    {
        $this->description = $description;
        return $this;
    }
}
