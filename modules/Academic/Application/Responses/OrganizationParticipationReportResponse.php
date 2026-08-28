<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class OrganizationParticipationReportResponse
{
    public function __construct(
        public string $organizationId,
        public string $organizationName,
        public int $enrollmentCount,
        public int $participatingCount,
        public float $participationRate,
    ) {}

    /** @return array{organization_id: string, organization_name: string, enrollment_count: int, participating_count: int, participation_rate: float} */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'organization_name' => $this->organizationName,
            'enrollment_count' => $this->enrollmentCount,
            'participating_count' => $this->participatingCount,
            'participation_rate' => $this->participationRate,
        ];
    }
}
