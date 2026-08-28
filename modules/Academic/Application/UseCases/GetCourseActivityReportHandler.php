<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Queries\GetCourseActivityReportQuery;
use Modules\Academic\Application\Responses\CourseActivityReportResponse;
use Modules\Academic\Application\Services\ReportCourseResolver;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class GetCourseActivityReportHandler
{
    private const int ACTIVE_WINDOW_DAYS = 30;

    public function __construct(
        private ReportCourseResolver $courseResolver,
        private EnrollmentRepository $enrollments,
        private UserRepository $users,
    ) {}

    /** @return list<CourseActivityReportResponse> */
    public function handle(GetCourseActivityReportQuery $query): array
    {
        $now = new DateTimeImmutable('now');

        return array_map(
            fn (Course $course): CourseActivityReportResponse => $this->reportFor($course, $now),
            $this->courseResolver->resolve($query->courseIds),
        );
    }

    private function reportFor(Course $course, DateTimeImmutable $now): CourseActivityReportResponse
    {
        $enrollments = $this->enrollments->all(courseId: $course->id());

        $activeCount = 0;
        $neverLoggedInCount = 0;
        $daysSinceLastLogin = [];

        foreach ($enrollments as $enrollment) {
            /** @var Enrollment $enrollment */
            $user = $this->users->findById($enrollment->userId());
            $lastLoginAt = $user?->lastLoginAt();

            if ($lastLoginAt === null) {
                $neverLoggedInCount++;

                continue;
            }

            $daysSince = $lastLoginAt->diff($now)->days;
            $daysSinceLastLogin[] = $daysSince;

            if ($daysSince <= self::ACTIVE_WINDOW_DAYS) {
                $activeCount++;
            }
        }

        return new CourseActivityReportResponse(
            courseId: $course->id()->value(),
            courseCode: $course->code()->value(),
            courseTitle: $course->title()->value(),
            enrollmentCount: count($enrollments),
            activeLast30DaysCount: $activeCount,
            neverLoggedInCount: $neverLoggedInCount,
            averageDaysSinceLastLogin: $daysSinceLastLogin === []
                ? null
                : round(array_sum($daysSinceLastLogin) / count($daysSinceLastLogin), 2),
        );
    }
}
