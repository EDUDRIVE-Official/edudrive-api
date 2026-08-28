<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class OrganizationAdoptionReportResponse
{
    /** @param list<array{month: string, count: int}> $monthlyEnrollments */
    public function __construct(
        public string $organizationId,
        public string $organizationName,
        public array $monthlyEnrollments,
    ) {}

    /** @return array{organization_id: string, organization_name: string, monthly_enrollments: list<array{month: string, count: int}>} */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'organization_name' => $this->organizationName,
            'monthly_enrollments' => $this->monthlyEnrollments,
        ];
    }
}
