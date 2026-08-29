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
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;
use Throwable;

final class ExportEnrollmentsJob implements ShouldQueue
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
        EnrollmentRepository $enrollments,
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
            static fn (Enrollment $enrollment): array => [
                $enrollment->id()->value(),
                $enrollment->courseId()->value(),
                $enrollment->userId(),
                (string) $enrollment->organizationId()?->value(),
                $enrollment->status()->value,
                $enrollment->source()->value,
            ],
            $enrollments->all(),
        );

        $csv = $csvWriter->toString(
            ['id', 'course_id', 'user_id', 'organization_id', 'status', 'source'],
            $rows,
        );

        $storagePath = sprintf('exports/enrollments/%s.csv', (string) Str::uuid());
        $exported = $exportFileWriter->write($storagePath, $csv);

        $auditLogger->log(new AuditEntry(
            action: 'export.enrollments',
            metadata: ['row_count' => count($rows), 'format' => 'csv'],
        ));

        $job->complete([
            'url' => $exported->url,
            'expires_at' => $exported->expiresAt->format(DateTimeInterface::ATOM),
            'row_count' => count($rows),
            'format' => 'csv',
            'storage_path' => $storagePath,
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

        $job->fail($exception?->getMessage() ?? 'Error desconocido al exportar las matriculas.', new DateTimeImmutable('now'));
        $jobs->save($job);

        Log::warning('Fallo la exportacion asincrona de matriculas.', [
            'async_job_id' => $this->asyncJobId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
