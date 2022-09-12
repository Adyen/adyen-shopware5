<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\EntityManager;

final class UserPreferenceManager implements UserPreferenceManagerInterface
{
    /** @var EntityManager */
    private $modelManager;

    public function __construct(EntityManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function save(UserPreference $userPreference): void
    {
        $this->modelManager->persist($userPreference);
        $this->modelManager->flush($userPreference);
    }
}
