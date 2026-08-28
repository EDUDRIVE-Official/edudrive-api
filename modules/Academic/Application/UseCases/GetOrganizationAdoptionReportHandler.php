<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetOrganizationAdoptionReportQuery;
use Modules\Academic\Application\Responses\OrganizationAdoptionReportResponse;
use Modules\Academic\Application\Services\ReportOrganizationResolver;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Organization\Domain\Aggregates\Organization;

final readonly class GetOrganizationAdoptionReportHandler
{
    public function __construct(
        private ReportOrganizationResolver $organizationResolver,
        private EnrollmentRepository $enrollments,
    ) {}

    /** @return list<OrganizationAdoptionReportResponse> */
    public function handle(GetOrganizationAdoptionReportQuery $query): array
    {
        return array_map(
            fn (Organization $organization): OrganizationAdoptionReportResponse => $this->reportFor($organization),
            $this->organizationResolver->resolve($query->organizationIds),
        );
    }

    private function reportFor(Organization $organization): OrganizationAdoptionReportResponse
    {
        $countsByMonth = [];

        foreach ($this->enrollments->all(organizationId: $organization->id()->value()) as $enrollment) {
            /** @var Enrollment $enrollment */
            $month = $enrollment->enrolledAt()->format('Y-m');
            $countsByMonth[$month] = ($countsByMonth[$month] ?? 0) + 1;
        }

        ksort($countsByMonth);

        $monthlyEnrollments = [];
        foreach ($countsByMonth as $month => $count) {
            $monthlyEnrollments[] = ['month' => $month, 'count' => $count];
        }

        return new OrganizationAdoptionReportResponse(
            organizationId: $organization->id()->value(),
            organizationName: $organization->name()->value(),
            monthlyEnrollments: $monthlyEnrollments,
        );
    }
}
