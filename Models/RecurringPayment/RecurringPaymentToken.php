<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

use AdyenPayment\Models\PaymentResultCode;
use AdyenPayment\Models\TokenIdentifier;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_adyen_payment_recurring_payment_token", indexes={
 *     @ORM\Index(name="idx_customer_id", columns={"customer_id"}),
 *     @ORM\Index(name="idx_psp_reference", columns={"psp_reference"}),
 *     @ORM\Index(name="idx_order_number", columns={"order_number"})
 * })
 */
class RecurringPaymentToken extends ModelEntity
{
    /**
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private string $id;

    /**
     * @ORM\Column(name="token_identifier", type="string", nullable=false)
     */
    private TokenIdentifier $tokenIdentifier;

    /**
     * @ORM\Column(name="customer_id", type="string", length=255, nullable=false)
     */
    private string $customerId;

    /**
     * @ORM\Column(name="recurring_detail_reference", type="text", nullable=false)
     */
    private string $recurringDetailReference;

    /**
     * @ORM\Column(name="psp_reference", type="string", length=255, nullable=false)
     */
    private string $pspReference;

    /**
     * @ORM\Column(name="order_number", type="string", length=255, nullable=false)
     */
    private string $orderNumber = '';

    /**
     * @ORM\Column(name="result_code", type="text", nullable=false)
     */
    private string $resultCode;

    /**
     * @ORM\Column(name="amount_value", type="integer", nullable=false)
     */
    private int $amountValue;

    /**
     * @ORM\Column(name="amount_currency", type="text", nullable=false)
     */
    private string $amountCurrency;

    /**
     * @ORM\Column(name="created_at", type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime_immutable")
     */
    private \DateTimeImmutable $updatedAt;

    private function __construct()
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public static function create(
        TokenIdentifier $id,
        string $customerId,
        string $recurringDetailReference,
        string $pspReference,
        string $orderNumber,
        PaymentResultCode $resultCode,
        int $amountValue,
        string $amountCurrency
    ): self {
        $new = new self();
        $new->id = $id->identifier();
        $new->tokenIdentifier = $id;
        $new->customerId = $customerId;
        $new->recurringDetailReference = $recurringDetailReference;
        $new->pspReference = $pspReference;
        $new->orderNumber = $orderNumber;
        $new->resultCode = $resultCode->resultCode();
        $new->amountValue = $amountValue;
        $new->amountCurrency = $amountCurrency;

        return $new;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tokenIdentifier(): TokenIdentifier
    {
        return $this->tokenIdentifier = TokenIdentifier::generateFromString($this->id);
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function recurringDetailReference(): string
    {
        return $this->recurringDetailReference;
    }

    public function pspReference(): string
    {
        return $this->pspReference;
    }

    public function orderNumber(): string
    {
        return $this->orderNumber;
    }

    public function resultCode(): string
    {
        return $this->resultCode;
    }

    public function amountValue(): int
    {
        return $this->amountValue;
    }

    public function amountCurrency(): string
    {
        return $this->amountCurrency;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isSubscription(): bool
    {
        return '' === $this->orderNumber();
    }

    public function isOneOffPayment(): bool
    {
        return '' !== $this->orderNumber();
    }
}
