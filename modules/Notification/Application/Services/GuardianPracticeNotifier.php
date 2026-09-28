<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

interface GuardianPracticeNotifier
{
    public function notify(string $userId, string $lessonId, string $lessonTitle, string $observationSubjectId): void;
}
