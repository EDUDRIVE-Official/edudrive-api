<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Jobs;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;
use Throwable;

final class ExportCoursesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $asyncJobId,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        AsyncJobRepository $jobs,
        CourseRepository $courses,
        ExportFileWriter $exportFileWriter,
        CsvWriter $csvWriter,
        AuditLogger $auditLogger,
    ): void {
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->start(new DateTimeImmutable('now'));
        $jobs->save($job);

        $rows = array_map(
            static fn (Course $course): array => [
                $course->id()->value(),
                $course->code()->value(),
                $course->title()->value(),
                (string) $course->description(),
                (string) $course->objectives(),
                (string) $course->prerequisites(),
                (string) $course->modality()?->value,
                (string) $course->durationHours(),
                $course->status()->value,
            ],
            $courses->all(),
        );

        $csv = $csvWriter->toString(
            ['id', 'code', 'title', 'description', 'objectives', 'prerequisites', 'modality', 'duration_hours', 'status'],
            $rows,
        );

        $storagePath = sprintf('exports/courses/%s.csv', (string) Str::uuid());
        $exported = $exportFileWriter->write($storagePath, $csv);

        $auditLogger->log(new AuditEntry(
            action: 'export.courses',
            metadata: ['row_count' => count($rows), 'format' => 'csv'],
        ));

        $job->complete([
            'url' => $exported->url,
            'expires_at' => $exported->expiresAt->format(DateTimeInterface::ATOM),
            'row_count' => count($rows),
            'format' => 'csv',
        ], new DateTimeImmutable('now'));
        $jobs->save($job);
    }

    public function failed(?Throwable $exception): void
    {
        $jobs = app(AsyncJobRepository::class);
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->fail($exception?->getMessage() ?? 'Error desconocido al exportar los cursos.', new DateTimeImmutable('now'));
        $jobs->save($job);

        Log::warning('Fallo la exportacion asincrona de cursos.', [
            'async_job_id' => $this->asyncJobId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
