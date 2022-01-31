<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use AdyenPayment\Models\UserPreference;

interface UserPreferenceManagerInterface
{
    public function upsertStoredMethodIdByUserId(int $userId, ?string $storedMethodId);

    public function updateStoredMethodId(UserPreference $userPreference, ?string $storedMethodId);
}
