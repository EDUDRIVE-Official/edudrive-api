<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetCourseProgressReportQuery;
use Modules\Academic\Application\Responses\CourseProgressReportResponse;
use Modules\Academic\Application\Services\ReportCourseResolver;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;

final readonly class GetCourseProgressReportHandler
{
    public function __construct(
        private ReportCourseResolver $courseResolver,
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progress,
        private CourseLessonCatalog $lessonCatalog,
    ) {}

    /** @return list<CourseProgressReportResponse> */
    public function handle(GetCourseProgressReportQuery $query): array
    {
        return array_map(
            fn (Course $course): CourseProgressReportResponse => $this->reportFor($course),
            $this->courseResolver->resolve($query->courseIds),
        );
    }

    private function reportFor(Course $course): CourseProgressReportResponse
    {
        $totalLessons = count($this->lessonCatalog->lessonIdsFor($course));
        $enrollments = $this->enrollments->all(courseId: $course->id());

        $percentages = array_map(
            fn (Enrollment $enrollment): float => $this->completionPercentageFor($enrollment, $totalLessons),
            $enrollments,
        );

        $enrollmentCount = count($enrollments);

        return new CourseProgressReportResponse(
            courseId: $course->id()->value(),
            courseCode: $course->code()->value(),
            courseTitle: $course->title()->value(),
            enrollmentCount: $enrollmentCount,
            averageCompletionPercentage: $enrollmentCount > 0 ? round(array_sum($percentages) / $enrollmentCount, 2) : 0.0,
            fullyCompletedCount: count(array_filter($percentages, static fn (float $percentage): bool => $percentage >= 100.0)),
        );
    }

    private function completionPercentageFor(Enrollment $enrollment, int $totalLessons): float
    {
        if ($totalLessons === 0) {
            return 0.0;
        }

        $progress = $this->progress->findByEnrollmentId($enrollment->id());

        return round(count($progress->completedLessonIds()) / $totalLessons * 100, 2);
    }
}
