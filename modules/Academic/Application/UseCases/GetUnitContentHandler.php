<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\CourseUnitNotFound;
use Modules\Academic\Application\Queries\GetUnitContentQuery;
use Modules\Academic\Application\Responses\UnitContentResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;

final readonly class GetUnitContentHandler
{
    public function __construct(
        private CourseRepository $courses,
        private UnitContentRepository $contents,
    ) {}

    public function handle(GetUnitContentQuery $query): UnitContentResponse
    {
        $courseId = CourseId::fromString($query->courseId);
        $unitId = CourseUnitId::fromString($query->unitId);
        $course = $this->courses->findById($courseId);

        if ($course === null) {
            throw CourseNotFound::withId($query->courseId);
        }

        if (! $course->ownsUnit($unitId)) {
            throw CourseUnitNotFound::create();
        }

        $content = $this->contents->findForCourseUnit($courseId, $unitId);

        if ($content === null) {
            throw CourseNotFound::withId($query->courseId);
        }

        return UnitContentResponse::fromUnitContent($courseId, $course->status(), $content);
    }
}
