<?php

namespace AdyenPayment\Tests\TestComponents\Components;

use AdyenPayment\Models\BaseEntity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="test_adyen_entity",
 *     indexes={
 *              @Index(name="type", columns={"type"})
 *          }
 *      )
 */
class TestEntity extends BaseEntity
{
}
