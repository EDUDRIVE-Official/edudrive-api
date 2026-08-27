<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use Modules\Admin\Application\Queries\GetSystemSummaryQuery;
use Modules\Admin\Application\Responses\SystemSummaryResponse;
use Modules\Admin\Domain\Repositories\SystemSummaryRepository;

final readonly class GetSystemSummaryHandler
{
    public function __construct(private SystemSummaryRepository $summary) {}

    public function handle(GetSystemSummaryQuery $query): SystemSummaryResponse
    {
        return new SystemSummaryResponse(
            totalUsers: $this->summary->countUsers(),
            totalEnrollments: $this->summary->countEnrollments(),
            totalAchievementsGranted: $this->summary->countAchievementsGranted(),
            totalCertificatesIssued: $this->summary->countCertificatesIssued(),
            totalSimulationSessions: $this->summary->countSimulationSessions(),
        );
    }
}
