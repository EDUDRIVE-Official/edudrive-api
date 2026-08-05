<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;

interface UnitContentRepository
{
    public function findForCourseUnit(CourseId $courseId, CourseUnitId $unitId): ?UnitContent;

    public function replaceAtomically(CourseId $courseId, CourseUnitId $unitId, UnitContent $content): ?UnitContent;
}
