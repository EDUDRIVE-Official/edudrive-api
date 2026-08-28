<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class OrganizationPerformanceReportResponse
{
    public function __construct(
        public string $organizationId,
        public string $organizationName,
        public int $attemptCount,
        public float $averageScore,
        public float $averagePercentage,
        public int $passedCount,
        public float $passRate,
    ) {}

    /** @return array{organization_id: string, organization_name: string, attempt_count: int, average_score: float, average_percentage: float, passed_count: int, pass_rate: float} */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'organization_name' => $this->organizationName,
            'attempt_count' => $this->attemptCount,
            'average_score' => $this->averageScore,
            'average_percentage' => $this->averagePercentage,
            'passed_count' => $this->passedCount,
            'pass_rate' => $this->passRate,
        ];
    }
}
