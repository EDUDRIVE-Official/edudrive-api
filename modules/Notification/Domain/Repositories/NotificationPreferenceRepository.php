<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Repositories;

use Modules\Notification\Domain\Aggregates\NotificationPreference;

interface NotificationPreferenceRepository
{
    public function save(NotificationPreference $preference): void;

    public function findByUserId(string $userId): ?NotificationPreference;
}
