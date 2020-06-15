<?php


namespace AdyenPayment\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Models\Order\Order;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_adyen_order_refund")
 */
class Refund
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Refund
     */
    public function setId(int $id): Refund
    {
        $this->id = $id;
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
     * @return Refund
     */
    public function setOrderId(int $orderId): Refund
    {
        $this->orderId = $orderId;
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
     * @return Refund
     */
    public function setOrder(Order $order): Refund
    {
        $this->order = $order;
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
     * @return Refund
     */
    public function setPspReference(string $pspReference): Refund
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
     * @return Refund
     */
    public function setCreatedAt(\DateTime $createdAt): Refund
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
     * @return Refund
     */
    public function setUpdatedAt(\DateTime $updatedAt): Refund
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
