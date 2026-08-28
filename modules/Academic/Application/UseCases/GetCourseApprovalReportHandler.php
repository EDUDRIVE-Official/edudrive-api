<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetCourseApprovalReportQuery;
use Modules\Academic\Application\Responses\CourseApprovalReportResponse;
use Modules\Academic\Application\Services\CourseExamAttemptsLookup;
use Modules\Academic\Application\Services\ReportCourseResolver;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\ExamAttempt;

final readonly class GetCourseApprovalReportHandler
{
    public function __construct(
        private ReportCourseResolver $courseResolver,
        private CourseExamAttemptsLookup $attemptsLookup,
    ) {}

    /** @return list<CourseApprovalReportResponse> */
    public function handle(GetCourseApprovalReportQuery $query): array
    {
        return array_map(
            fn (Course $course): CourseApprovalReportResponse => $this->reportFor($course),
            $this->courseResolver->resolve($query->courseIds),
        );
    }

    private function reportFor(Course $course): CourseApprovalReportResponse
    {
        $attempts = $this->attemptsLookup->submittedAttemptsFor($course->id());
        $attemptCount = count($attempts);
        $passedCount = count(array_filter($attempts, static fn (ExamAttempt $attempt): bool => $attempt->passed()));

        return new CourseApprovalReportResponse(
            courseId: $course->id()->value(),
            courseCode: $course->code()->value(),
            courseTitle: $course->title()->value(),
            attemptCount: $attemptCount,
            passedCount: $passedCount,
            passRate: $attemptCount > 0 ? round($passedCount / $attemptCount * 100, 2) : 0.0,
        );
    }
}
