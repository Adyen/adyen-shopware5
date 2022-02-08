<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use AdyenPayment\Models\UserPreference;

interface UserPreferenceManagerInterface
{
    public function save(UserPreference $userPreference): void;
}
