<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetCoursePerformanceReportQuery;
use Modules\Academic\Application\Responses\CoursePerformanceReportResponse;
use Modules\Academic\Application\Services\CourseExamAttemptsLookup;
use Modules\Academic\Application\Services\ReportCourseResolver;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\ExamAttempt;

final readonly class GetCoursePerformanceReportHandler
{
    public function __construct(
        private ReportCourseResolver $courseResolver,
        private CourseExamAttemptsLookup $attemptsLookup,
    ) {}

    /** @return list<CoursePerformanceReportResponse> */
    public function handle(GetCoursePerformanceReportQuery $query): array
    {
        return array_map(
            fn (Course $course): CoursePerformanceReportResponse => $this->reportFor($course),
            $this->courseResolver->resolve($query->courseIds),
        );
    }

    private function reportFor(Course $course): CoursePerformanceReportResponse
    {
        $attempts = $this->attemptsLookup->submittedAttemptsFor($course->id());
        $attemptCount = count($attempts);

        return new CoursePerformanceReportResponse(
            courseId: $course->id()->value(),
            courseCode: $course->code()->value(),
            courseTitle: $course->title()->value(),
            attemptCount: $attemptCount,
            averageScore: $attemptCount > 0
                ? round(array_sum(array_map(static fn (ExamAttempt $attempt): int => $attempt->score(), $attempts)) / $attemptCount, 2)
                : 0.0,
            averagePercentage: $attemptCount > 0
                ? round(array_sum(array_map(static fn (ExamAttempt $attempt): int => $attempt->percentage(), $attempts)) / $attemptCount, 2)
                : 0.0,
        );
    }
}
