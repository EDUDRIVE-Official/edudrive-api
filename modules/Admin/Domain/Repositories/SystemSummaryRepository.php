<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\Repositories;

interface SystemSummaryRepository
{
    public function countUsers(): int;

    public function countEnrollments(): int;

    public function countAchievementsGranted(): int;

    public function countCertificatesIssued(): int;

    public function countSimulationSessions(): int;
}
