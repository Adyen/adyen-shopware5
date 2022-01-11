<?php

namespace AdyenPayment\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Order\Order;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_adyen_order_payment_info", indexes={
 *     @ORM\Index(name="idx_ordernumber", columns={"ordernumber"})
 * })
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
     *
     * @ORM\Column(name="ordermail_variables", type="text", nullable=true)
     */
    private $ordermailVariables;

    /**
     * @var string
     * @ORM\Column(name="ordernumber", type="string", length=255, nullable=true)
     */
    private $ordernumber;

    /**
     * @var string
     * @ORM\Column(name="payment_data", type="text", nullable=true)
     */
    private $paymentData;

    /**
     * @var string
     * @ORM\Column(name="stored_method_id", type="text", nullable=true)
     */
    private $storedMethodId;

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
     *
     * @return static
     */
    public function setId(int $id): self
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
     *
     * @return static
     */
    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
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
     *
     * @return static
     */
    public function setOrder(Order $order = null): self
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
     *
     * @return static
     */
    public function setPspReference(string $pspReference): self
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
     *
     * @return static
     */
    public function setCreatedAt(\DateTime $createdAt): self
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
     *
     * @return static
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
     *
     * @return static
     */
    public function setResultCode(string $resultCode): self
    {
        $this->resultCode = $resultCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrdermailVariables()
    {
        return $this->ordermailVariables;
    }

    /**
     * @param string|null $ordermailVariables
     *
     * @return static
     */
    public function setOrdermailVariables($ordermailVariables): self
    {
        $this->ordermailVariables = $ordermailVariables;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrdernumber()
    {
        return $this->ordernumber;
    }

    /**
     * @param string|null $ordernumber
     *
     * @return static
     */
    public function setOrderNumber($ordernumber): self
    {
        $this->ordernumber = $ordernumber;

        return $this;
    }

    public function getPaymentData(): string
    {
        return $this->paymentData;
    }

    public function setPaymentData(string $paymentData): self
    {
        $this->paymentData = $paymentData;

        return $this;
    }

    public function getStoredMethodId(): string
    {
        return $this->storedMethodId;
    }

    public function setStoredMethodId(string $storedMethodId): self
    {
        $this->storedMethodId = $storedMethodId;

        return $this;
    }
}
