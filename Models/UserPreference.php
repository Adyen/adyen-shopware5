<?php

declare(strict_types=1);

namespace AdyenPayment\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_adyen_user_preference")
 */
class UserPreference extends ModelEntity
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
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var string
     * @ORM\Column(name="stored_method_id", type="text", nullable=true)
     */
    private $storedMethodId;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getStoredMethodId(): ?string
    {
        return $this->storedMethodId;
    }

    public function setStoredMethodId(?string $storedMethodId): self
    {
        $this->storedMethodId = $storedMethodId;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'storedMethodId' => $this->getStoredMethodId(),
        ];
    }
}
