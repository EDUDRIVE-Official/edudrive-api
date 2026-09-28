<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Console;

use Illuminate\Console\Command;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Gamification\Application\Services\CourseCompletionRewarder;

final class ReconcileCourseCompletionRewardsCommand extends Command
{
    /** @var string */
    protected $signature = 'gamification:reconcile-course-rewards {--dry-run : Solo informa cuántas matrículas serían procesadas}';

    /** @var string */
    protected $description = 'Reconcilia XP e insignias de matrículas completadas sin duplicar recompensas.';

    public function handle(
        EnrollmentRepository $enrollments,
        CourseCompletionRewarder $rewarder,
    ): int {
        $completed = $enrollments->all(status: EnrollmentStatus::Completed);
        if ($this->option('dry-run')) {
            $this->info(sprintf('%d matrícula(s) completada(s) están disponibles para reconciliar.', count($completed)));

            return self::SUCCESS;
        }

        $processed = 0;
        foreach ($completed as $enrollment) {
            $rewarder->reward($enrollment->userId(), $enrollment->courseId()->value(), null);
            $processed++;
        }

        $this->info(sprintf('%d matrícula(s) reconciliada(s). Las recompensas existentes se conservaron sin duplicados.', $processed));

        return self::SUCCESS;
    }
}
