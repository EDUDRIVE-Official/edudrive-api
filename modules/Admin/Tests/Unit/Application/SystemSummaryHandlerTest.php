<?php

declare(strict_types=1);

use Modules\Admin\Application\Queries\GetSystemSummaryQuery;
use Modules\Admin\Application\Responses\SystemSummaryResponse;
use Modules\Admin\Application\UseCases\GetSystemSummaryHandler;
use Modules\Admin\Domain\Repositories\SystemSummaryRepository;

final class InMemorySystemSummaryRepository implements SystemSummaryRepository
{
    public function __construct(
        private int $users = 0,
        private int $enrollments = 0,
        private int $achievementsGranted = 0,
        private int $certificatesIssued = 0,
        private int $simulationSessions = 0,
    ) {}

    public function countUsers(): int
    {
        return $this->users;
    }

    public function countEnrollments(): int
    {
        return $this->enrollments;
    }

    public function countAchievementsGranted(): int
    {
        return $this->achievementsGranted;
    }

    public function countCertificatesIssued(): int
    {
        return $this->certificatesIssued;
    }

    public function countSimulationSessions(): int
    {
        return $this->simulationSessions;
    }
}

it('compone el resumen del sistema a partir de los conteos', function (): void {
    $summary = new InMemorySystemSummaryRepository(
        users: 10,
        enrollments: 25,
        achievementsGranted: 5,
        certificatesIssued: 3,
        simulationSessions: 8,
    );

    $response = (new GetSystemSummaryHandler($summary))->handle(new GetSystemSummaryQuery);

    expect($response)->toBeInstanceOf(SystemSummaryResponse::class)
        ->and($response->totalUsers)->toBe(10)
        ->and($response->totalEnrollments)->toBe(25)
        ->and($response->totalAchievementsGranted)->toBe(5)
        ->and($response->totalCertificatesIssued)->toBe(3)
        ->and($response->totalSimulationSessions)->toBe(8);
});
