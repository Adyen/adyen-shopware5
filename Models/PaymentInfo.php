<?php

namespace MeteorAdyen\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Order\Order;

/**
 * @ORM\Entity
 * @ORM\Table(name="adyen_order_payment_info")
 */
class PaymentInfo extends ModelEntity
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
     * @var string
     * @ORM\Column(name="result_code", type="text", nullable=true)
     */
    private $resultCode;

    /**
     * @var string
     * @ORM\Column(name="idempotency_key", type="text")
     */
    private $idempotencyKey;

    /**
     * PaymenntInfo constructor
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
     * @return PaymentInfo
     */
    public function setId(int $id): PaymentInfo
    {
        $this->id = $id;
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
     * @return PaymentInfo
     */
    public function setOrderId(int $orderId): PaymentInfo
    {
        $this->orderId = $orderId;
    }


    /**
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order|null $order
     * @return PaymentInfo
     */
    public function setOrder(Order $order = null): PaymentInfo
    {
        $this->order = $order;
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
     * @return PaymentInfo
     */
    public function setPspReference(string $pspReference): PaymentInfo
    {
        $this->pspReference = $pspReference;
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
     * @return PaymentInfo
     */
    public function setCreatedAt(\DateTime $createdAt): PaymentInfo
    {
        $this->createdAt = $createdAt;
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
     * @return PaymentInfo
     */
    public function setUpdatedAt(\DateTime $updatedAt): PaymentInfo
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getResultCode(): string
    {
        return $this->resultCode;
    }

    /**
     * @param string $resultCode
     * @return PaymentInfo
     */
    public function setResultCode(string $resultCode): PaymentInfo
    {
        $this->resultCode = $resultCode;
    }

    /**
     * @return string
     */
    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    /**
     * @param string $idempotencyKey
     * @return PaymentInfo
     */
    public function setIdempotencyKey(string $idempotencyKey): PaymentInfo
    {
        $this->idempotencyKey = $idempotencyKey;
    }
}
