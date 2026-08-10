<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Entities\CourseVersion;
use Modules\Academic\Domain\ValueObjects\CourseId;

interface CourseVersionRepository
{
    public function save(CourseVersion $version): void;

    /**
     * @return list<CourseVersion>
     */
    public function allForCourse(CourseId $courseId): array;

    public function findByNumber(CourseId $courseId, int $versionNumber): ?CourseVersion;

    public function nextVersionNumber(CourseId $courseId): int;
}
