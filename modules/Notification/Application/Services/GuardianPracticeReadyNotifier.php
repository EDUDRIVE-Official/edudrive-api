<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

interface GuardianPracticeReadyNotifier
{
    public function notifyForMinor(string $minorUserId, string $lessonId, string $lessonTitle): void;
}
