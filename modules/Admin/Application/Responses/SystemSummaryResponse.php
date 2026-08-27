<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Responses;

final readonly class SystemSummaryResponse
{
    public function __construct(
        public int $totalUsers,
        public int $totalEnrollments,
        public int $totalAchievementsGranted,
        public int $totalCertificatesIssued,
        public int $totalSimulationSessions,
    ) {}

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'total_users' => $this->totalUsers,
            'total_enrollments' => $this->totalEnrollments,
            'total_achievements_granted' => $this->totalAchievementsGranted,
            'total_certificates_issued' => $this->totalCertificatesIssued,
            'total_simulation_sessions' => $this->totalSimulationSessions,
        ];
    }
}
