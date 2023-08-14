<?php


namespace AdyenPayment\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class BaseEntity
 * @package AdyenPayment\Models
 *
 * Base entity had to be created in order to enable AdyenEntity and TestEntity to use different tables.
 * If for example TestEntity extends Adyen entity instead of a base entity due to double table annotation
 * (one in AdyenEntity one in TestEntity) doctrine fails to properly transpile dql to sql therefore base class with
 * no table annotation has been created.
 */
class BaseEntity extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=128, nullable=false)
     */
    protected $type;
    /**
     * @var string $index_1
     *
     * @ORM\Column(name="index_1", type="string", length=255, nullable=true)
     */
    protected $index_1;
    /**
     * @var string $index_2
     *
     * @ORM\Column(name="index_2", type="string", length=255, nullable=true)
     */
    protected $index_2;
    /**
     * @var string $index_3
     *
     * @ORM\Column(name="index_3", type="string", length=255, nullable=true)
     */
    protected $index_3;
    /**
     * @var string $index_4
     *
     * @ORM\Column(name="index_4", type="string", length=255, nullable=true)
     */
    protected $index_4;
    /**
     * @var string $index_5
     *
     * @ORM\Column(name="index_5", type="string", length=255, nullable=true)
     */
    protected $index_5;
    /**
     * @var string $index_6
     *
     * @ORM\Column(name="index_6", type="string", length=255, nullable=true)
     */
    protected $index_6;
    /**
     * @var string $index_7
     *
     * @ORM\Column(name="index_7", type="string", length=255, nullable=true)
     */
    protected $index_7;
    /**
     * @var string $index_8
     *
     * @ORM\Column(name="index_8", type="string", length=255, nullable=true)
     */
    protected $index_8;
    /**
     * @var string $index_9
     *
     * @ORM\Column(name="index_9", type="string", length=255, nullable=true)
     */
    protected $index_9;
    /**
     * @var string $data
     *
     * @ORM\Column(name="data", type="text", nullable=false)
     */
    protected $data;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getIndex_1()
    {
        return $this->index_1;
    }

    /**
     * @param string $index_1
     */
    public function setIndex_1($index_1)
    {
        $this->index_1 = $index_1;
    }

    /**
     * @return string
     */
    public function getIndex_2()
    {
        return $this->index_2;
    }

    /**
     * @param string $index_2
     */
    public function setIndex_2($index_2)
    {
        $this->index_2 = $index_2;
    }

    /**
     * @return string
     */
    public function getIndex_3()
    {
        return $this->index_3;
    }

    /**
     * @param string $index_3
     */
    public function setIndex_3($index_3)
    {
        $this->index_3 = $index_3;
    }

    /**
     * @return string
     */
    public function getIndex_4()
    {
        return $this->index_4;
    }

    /**
     * @param string $index_4
     */
    public function setIndex_4($index_4)
    {
        $this->index_4 = $index_4;
    }

    /**
     * @return string
     */
    public function getIndex_5()
    {
        return $this->index_5;
    }

    /**
     * @param string $index_5
     */
    public function setIndex_5($index_5)
    {
        $this->index_5 = $index_5;
    }

    /**
     * @return string
     */
    public function getIndex_6()
    {
        return $this->index_6;
    }

    /**
     * @param string $index_6
     */
    public function setIndex_6($index_6)
    {
        $this->index_6 = $index_6;
    }

    /**
     * @return string
     */
    public function getIndex_7()
    {
        return $this->index_7;
    }

    /**
     * @param string $index_7
     */
    public function setIndex_7($index_7)
    {
        $this->index_7 = $index_7;
    }

    /**
     * @return string
     */
    public function getIndex_8()
    {
        return $this->index_8;
    }

    /**
     * @param string $index_8
     */
    public function setIndex_8($index_8)
    {
        $this->index_8 = $index_8;
    }

    /**
     * @return string
     */
    public function getIndex_9(): string
    {
        return $this->index_9;
    }

    /**
     * @param string $index_9
     */
    public function setIndex_9($index_9): void
    {
        $this->index_9 = $index_9;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
