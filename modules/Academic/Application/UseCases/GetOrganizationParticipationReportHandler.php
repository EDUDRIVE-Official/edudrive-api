<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\GetOrganizationParticipationReportQuery;
use Modules\Academic\Application\Responses\OrganizationParticipationReportResponse;
use Modules\Academic\Application\Services\ReportOrganizationResolver;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Organization\Domain\Aggregates\Organization;

final readonly class GetOrganizationParticipationReportHandler
{
    public function __construct(
        private ReportOrganizationResolver $organizationResolver,
        private EnrollmentRepository $enrollments,
        private EnrollmentProgressRepository $progress,
    ) {}

    /** @return list<OrganizationParticipationReportResponse> */
    public function handle(GetOrganizationParticipationReportQuery $query): array
    {
        return array_map(
            fn (Organization $organization): OrganizationParticipationReportResponse => $this->reportFor($organization),
            $this->organizationResolver->resolve($query->organizationIds),
        );
    }

    private function reportFor(Organization $organization): OrganizationParticipationReportResponse
    {
        $enrollments = $this->enrollments->all(organizationId: $organization->id()->value());
        $enrollmentCount = count($enrollments);

        $participatingCount = count(array_filter(
            $enrollments,
            fn (Enrollment $enrollment): bool => $this->progress->findByEnrollmentId($enrollment->id())->completedLessonIds() !== [],
        ));

        return new OrganizationParticipationReportResponse(
            organizationId: $organization->id()->value(),
            organizationName: $organization->name()->value(),
            enrollmentCount: $enrollmentCount,
            participatingCount: $participatingCount,
            participationRate: $enrollmentCount > 0 ? round($participatingCount / $enrollmentCount * 100, 2) : 0.0,
        );
    }
}
