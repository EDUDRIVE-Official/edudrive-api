<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

interface CourseCompletionNotifier
{
    public function notify(string $userId, string $courseId, string $courseTitle): void;
}
