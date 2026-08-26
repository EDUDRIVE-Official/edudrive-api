<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Repositories;

use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\ValueObjects\NotificationId;

interface NotificationRepository
{
    public function save(Notification $notification): void;

    public function findById(NotificationId $id): ?Notification;

    /** @return list<Notification> */
    public function allForUser(string $userId): array;
}
