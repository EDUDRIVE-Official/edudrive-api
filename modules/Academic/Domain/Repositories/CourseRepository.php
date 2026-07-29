<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Entities\Course;
use Modules\Academic\Domain\ValueObjects\CourseCode;

interface CourseRepository
{
    public function save(Course $course): void;

    public function findByCode(CourseCode $code): ?Course;

    public function exists(CourseCode $code): bool;

    public function delete(Course $course): void;
}