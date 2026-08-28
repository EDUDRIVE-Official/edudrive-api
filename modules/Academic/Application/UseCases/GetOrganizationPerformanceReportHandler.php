<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetOrganizationPerformanceReportQuery;
use Modules\Academic\Application\Responses\OrganizationPerformanceReportResponse;
use Modules\Academic\Application\Services\CourseExamAttemptsLookup;
use Modules\Academic\Application\Services\ReportOrganizationResolver;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Organization\Domain\Aggregates\Organization;

final readonly class GetOrganizationPerformanceReportHandler
{
    public function __construct(
        private ReportOrganizationResolver $organizationResolver,
        private EnrollmentRepository $enrollments,
        private CourseExamAttemptsLookup $attemptsLookup,
    ) {}

    /** @return list<OrganizationPerformanceReportResponse> */
    public function handle(GetOrganizationPerformanceReportQuery $query): array
    {
        return array_map(
            fn (Organization $organization): OrganizationPerformanceReportResponse => $this->reportFor($organization),
            $this->organizationResolver->resolve($query->organizationIds),
        );
    }

    private function reportFor(Organization $organization): OrganizationPerformanceReportResponse
    {
        $enrollments = $this->enrollments->all(organizationId: $organization->id()->value());

        /** @var array<string, list<string>> $userIdsByCourse */
        $userIdsByCourse = [];
        foreach ($enrollments as $enrollment) {
            /** @var Enrollment $enrollment */
            $userIdsByCourse[$enrollment->courseId()->value()][] = $enrollment->userId();
        }

        $attempts = [];
        foreach ($userIdsByCourse as $courseId => $userIds) {
            foreach ($this->attemptsLookup->submittedAttemptsFor(CourseId::fromString($courseId)) as $attempt) {
                if (in_array($attempt->userId(), $userIds, true)) {
                    $attempts[] = $attempt;
                }
            }
        }

        $attemptCount = count($attempts);
        $passedCount = count(array_filter($attempts, static fn (ExamAttempt $attempt): bool => $attempt->passed()));

        return new OrganizationPerformanceReportResponse(
            organizationId: $organization->id()->value(),
            organizationName: $organization->name()->value(),
            attemptCount: $attemptCount,
            averageScore: $attemptCount > 0
                ? round(array_sum(array_map(static fn (ExamAttempt $attempt): int => $attempt->score(), $attempts)) / $attemptCount, 2)
                : 0.0,
            averagePercentage: $attemptCount > 0
                ? round(array_sum(array_map(static fn (ExamAttempt $attempt): int => $attempt->percentage(), $attempts)) / $attemptCount, 2)
                : 0.0,
            passedCount: $passedCount,
            passRate: $attemptCount > 0 ? round($passedCount / $attemptCount * 100, 2) : 0.0,
        );
    }
}
