<?php

namespace AdyenPayment\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="s_plugin_adyen_entity",
 *     indexes={
 *              @ORM\Index(name="type", columns={"type"})
 *          }
 *      )
 */
class AdyenEntity extends BaseEntity
{
}
