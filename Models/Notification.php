<?php

namespace AdyenPayment\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Order\Order;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_adyen_order_notification")
 */
class Notification extends ModelEntity implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="order_id", type="integer")
     */
    private $orderId;

    /**
     * @var Order|null
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Order", cascade={"remove"})
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     */
    private $order;

    /**
     * @var string
     * @ORM\Column(name="psp_reference", type="text")
     */
    private $pspReference;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="processed_at", type="datetime", nullable=true)
     */
    private $processedAt;

    /**
     * @var string
     * @ORM\Column(name="status", type="text")
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="paymentMethod", type="text")
     */
    private $paymentMethod;

    /**
     * @var string
     * @ORM\Column(name="event_code", type="text")
     */
    private $eventCode;

    /**
     * @var boolean
     * @ORM\Column(name="success", type="boolean")
     */
    private $success;

    /**
     * @var string
     * @ORM\Column(name="merchant_account_code", type="text")
     */
    private $merchantAccountCode;

    /**
     * @var float
     * @ORM\Column(name="amount_value", type="decimal", precision=19, scale=4)
     */
    private $amountValue;

    /**
     * @var string
     * @ORM\Column(name="amount_currency", type="text")
     */
    private $amountCurrency;

    /**
     * @var string
     * @ORM\Column(name="error_details", type="text")
     */
    private $errorDetails;

    /**
     * Notification constructor
     */
    public function __construct()
    {
        $this->setCreatedAt(new \DateTime('now'));
        $this->setUpdatedAt(new \DateTime('now'));
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Notification
     */
    public function setId(int $id): Notification
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Order|null
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @param Order|null $order
     * @return Notification
     */
    public function setOrder(Order $order): Notification
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     * @return Notification
     */
    public function setOrderId(int $orderId): Notification
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPspReference(): string
    {
        return $this->pspReference;
    }

    /**
     * @param string $pspReference
     * @return Notification
     */
    public function setPspReference(string $pspReference): Notification
    {
        $this->pspReference = $pspReference;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Notification
     */
    public function setCreatedAt(\DateTime $createdAt): Notification
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Notification
     */
    public function setUpdatedAt(\DateTime $updatedAt): Notification
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getProcessedAt(): \DateTime
    {
        return $this->processedAt;
    }

    /**
     * @param \DateTime $processedAt
     * @return Notification
     */
    public function setProcessedAt(\DateTime $processedAt): Notification
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Notification
     */
    public function setStatus(string $status): Notification
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return Notification
     */
    public function setPaymentMethod(string $paymentMethod): Notification
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventCode(): string
    {
        return $this->eventCode;
    }

    /**
     * @param string $eventCode
     * @return Notification
     */
    public function setEventCode(string $eventCode): Notification
    {
        $this->eventCode = $eventCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return Notification
     */
    public function setSuccess(bool $success): Notification
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantAccountCode(): string
    {
        return $this->merchantAccountCode;
    }

    /**
     * @param string $merchantAccountCode
     * @return Notification
     */
    public function setMerchantAccountCode(string $merchantAccountCode): Notification
    {
        $this->merchantAccountCode = $merchantAccountCode;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmountValue(): float
    {
        return $this->amountValue;
    }

    /**
     * @param float $amountValue
     * @return Notification
     */
    public function setAmountValue(float $amountValue): Notification
    {
        $this->amountValue = $amountValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getAmountCurrency(): string
    {
        return $this->amountCurrency;
    }

    /**
     * @param string $amountCurrency
     * @return Notification
     */
    public function setAmountCurrency(string $amountCurrency): Notification
    {
        $this->amountCurrency = $amountCurrency;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorDetails(): string
    {
        return $this->errorDetails;
    }

    /**
     * @param string $errorDetails
     * @return Notification
     */
    public function setErrorDetails(string $errorDetails): Notification
    {
        $this->errorDetails = $errorDetails;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'pspReference' => $this->getPspReference(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt(),
            'status' => $this->getStatus(),
            'paymentMethod' => $this->getPaymentMethod(),
            'eventCode' => $this->getEventCode(),
            'success' => $this->isSuccess(),
            'merchantAccountCode' => $this->getMerchantAccountCode(),
            'amountValue' => $this->getAmountValue(),
            'amountCurrency' => $this->getAmountCurrency(),
            'errorDetails' => $this->getErrorDetails(),
            'orderId' => $this->getOrderId()
        ];
    }
}
