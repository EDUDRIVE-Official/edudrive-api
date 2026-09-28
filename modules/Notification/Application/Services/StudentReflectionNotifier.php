<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

interface StudentReflectionNotifier
{
    public function notify(string $guardianUserId, string $minorName, string $lessonTitle): void;
}
