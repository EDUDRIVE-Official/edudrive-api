<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class OrganizationCompletionReportResponse
{
    public function __construct(
        public string $organizationId,
        public string $organizationName,
        public int $enrollmentCount,
        public int $completedCount,
        public float $completionRate,
    ) {}

    /** @return array{organization_id: string, organization_name: string, enrollment_count: int, completed_count: int, completion_rate: float} */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'organization_name' => $this->organizationName,
            'enrollment_count' => $this->enrollmentCount,
            'completed_count' => $this->completedCount,
            'completion_rate' => $this->completionRate,
        ];
    }
}
