<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

interface GuardianPracticeReadyNotificationResolver
{
    public function resolve(string $guardianUserId, string $minorUserId, string $lessonId): void;
}
