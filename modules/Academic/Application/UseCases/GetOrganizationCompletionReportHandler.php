<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetOrganizationCompletionReportQuery;
use Modules\Academic\Application\Responses\OrganizationCompletionReportResponse;
use Modules\Academic\Application\Services\ReportOrganizationResolver;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Organization\Domain\Aggregates\Organization;

final readonly class GetOrganizationCompletionReportHandler
{
    public function __construct(
        private ReportOrganizationResolver $organizationResolver,
        private EnrollmentRepository $enrollments,
    ) {}

    /** @return list<OrganizationCompletionReportResponse> */
    public function handle(GetOrganizationCompletionReportQuery $query): array
    {
        return array_map(
            fn (Organization $organization): OrganizationCompletionReportResponse => $this->reportFor($organization),
            $this->organizationResolver->resolve($query->organizationIds),
        );
    }

    private function reportFor(Organization $organization): OrganizationCompletionReportResponse
    {
        $enrollments = $this->enrollments->all(organizationId: $organization->id()->value());
        $enrollmentCount = count($enrollments);

        $completedCount = count(array_filter(
            $enrollments,
            static fn (Enrollment $enrollment): bool => $enrollment->status() === EnrollmentStatus::Completed,
        ));

        return new OrganizationCompletionReportResponse(
            organizationId: $organization->id()->value(),
            organizationName: $organization->name()->value(),
            enrollmentCount: $enrollmentCount,
            completedCount: $completedCount,
            completionRate: $enrollmentCount > 0 ? round($completedCount / $enrollmentCount * 100, 2) : 0.0,
        );
    }
}
