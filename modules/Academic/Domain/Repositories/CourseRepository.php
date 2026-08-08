<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Closure;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;

interface CourseRepository
{
    public function save(Course $course): void;

    /** @param Closure(Course): void $mutation */
    public function updateAtomically(CourseId $id, Closure $mutation): ?Course;

    /** @param Closure(Course, UnitContentCoverage): void $mutation */
    public function updateAtomicallyWithContentCoverage(CourseId $id, Closure $mutation): ?Course;

    public function findById(CourseId $id): ?Course;

    public function findByCode(CourseCode $code): ?Course;

    public function existsByCode(CourseCode $code): bool;

    /**
     * @return list<Course>
     */
    public function all(): array;
}
