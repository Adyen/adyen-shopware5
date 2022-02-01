<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\EntityManager;

final class UserPreferenceManager implements UserPreferenceManagerInterface
{
    private EntityManager $modelManager;

    public function __construct(EntityManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function upsertStoredMethodIdByUserId(int $userId, ?string $storedMethodId): void
    {
        $userPreferenceRepository = $this->modelManager->getRepository(UserPreference::class);
        $userPreference = $userPreferenceRepository->findOneBy(['userId' => $userId]);

        if (null === $userPreference) {
            $userPreference = new UserPreference();
            $userPreference->setUserId($userId);
        }

        $userPreference = $userPreference->setStoredMethodId($storedMethodId);

        $this->modelManager->persist($userPreference);
        $this->modelManager->flush($userPreference);
    }
}
