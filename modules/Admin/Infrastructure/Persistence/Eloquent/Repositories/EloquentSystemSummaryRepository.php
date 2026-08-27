<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentModel;
use Modules\Admin\Domain\Repositories\SystemSummaryRepository;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Models\CertificateModel;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\UserAchievementModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\SimulationSessionModel;

/**
 * Lee directamente los modelos Eloquent de otros módulos para producir conteos
 * agregados de solo lectura. Es una excepción deliberada al aislamiento estricto
 * entre módulos, documentada en docs/plans/2026-08-27-panel-administrativo-eng059-design.md,
 * limitada a este reporte: no hay invariantes de dominio que proteger en un conteo.
 */
final readonly class EloquentSystemSummaryRepository implements SystemSummaryRepository
{
    public function countUsers(): int
    {
        return UserModel::query()->count();
    }

    public function countEnrollments(): int
    {
        return EnrollmentModel::query()->count();
    }

    public function countAchievementsGranted(): int
    {
        return UserAchievementModel::query()->count();
    }

    public function countCertificatesIssued(): int
    {
        return CertificateModel::query()->count();
    }

    public function countSimulationSessions(): int
    {
        return SimulationSessionModel::query()->count();
    }
}
