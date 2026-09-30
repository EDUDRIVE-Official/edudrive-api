<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Services;

interface CourseCompletionRewarder
{
    public function reward(string $userId, string $courseId, ?string $competencyId): void;
}
