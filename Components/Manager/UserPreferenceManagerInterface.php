<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

interface UserPreferenceManagerInterface
{
    public function upsertStoredMethodIdByUserId(int $userId, ?string $storedMethodId);
}
