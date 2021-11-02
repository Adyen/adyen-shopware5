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
     * @return $this
     */
    public function setId(int $id)
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
     * @return $this
     */
    public function setOrderId(int $orderId)
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
     * @return $this
     */
    public function setOrder(Order $order = null)
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
     * @return $this
     */
    public function setPspReference(string $pspReference)
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
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
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
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
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
     * @return $this
     */
    public function setResultCode(string $resultCode)
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
     * @return $this
     */
    public function setOrdermailVariables($ordermailVariables)
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
     * @return $this
     */
    public function setOrderNumber($ordernumber)
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
}
