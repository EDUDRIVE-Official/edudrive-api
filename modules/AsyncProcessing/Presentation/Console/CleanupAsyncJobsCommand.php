<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Presentation\Console;

use DateTimeImmutable;
use Illuminate\Console\Command as ConsoleCommand;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\FileStorage\Application\Contracts\FileStorage;

final class CleanupAsyncJobsCommand extends ConsoleCommand
{
    /**
     * @var string
     */
    protected $signature = 'async-processing:cleanup';

    /**
     * @var string
     */
    protected $description = 'Borra los archivos de exportacion huerfanos y purga los trabajos asincronos finalizados mas alla del periodo de retencion configurado.';

    public function handle(AsyncJobRepository $jobs, FileStorage $fileStorage): int
    {
        $retentionHours = (int) config('async_processing.retention_hours');
        $threshold = new DateTimeImmutable(sprintf('-%d hours', $retentionHours));

        $expired = $jobs->allCompletedOrFailedBefore($threshold);
        $filesDeleted = 0;

        foreach ($expired as $job) {
            $result = $job->result();
            $storagePath = $result['storage_path'] ?? null;

            if ($job->status() === AsyncJobStatus::Completed && str_starts_with($job->type(), 'export.') && is_string($storagePath)) {
                $fileStorage->delete($storagePath);
                $filesDeleted++;
            }

            $jobs->delete($job->id());
        }

        $this->info(sprintf(
            'Trabajos asincronos purgados: %d. Archivos de exportacion eliminados: %d.',
            count($expired),
            $filesDeleted,
        ));

        return self::SUCCESS;
    }
}
