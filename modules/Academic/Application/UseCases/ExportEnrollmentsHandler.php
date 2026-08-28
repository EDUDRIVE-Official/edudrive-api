<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeInterface;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\ExportEnrollmentsCommand;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Foundation\Application\Responses\ExportResponse;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;

final readonly class ExportEnrollmentsHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private ExportFileWriter $exportFileWriter,
        private CsvWriter $csvWriter,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(ExportEnrollmentsCommand $command): ExportResponse
    {
        $enrollments = $this->enrollments->all();

        $rows = array_map(
            static fn (Enrollment $enrollment): array => [
                $enrollment->id()->value(),
                $enrollment->courseId()->value(),
                $enrollment->userId(),
                (string) $enrollment->organizationId()?->value(),
                $enrollment->status()->value,
                $enrollment->source()->value,
            ],
            $enrollments,
        );

        $csv = $this->csvWriter->toString(
            ['id', 'course_id', 'user_id', 'organization_id', 'status', 'source'],
            $rows,
        );

        $storagePath = sprintf('exports/enrollments/%s.csv', (string) Str::uuid());
        $exported = $this->exportFileWriter->write($storagePath, $csv);

        $this->auditLogger->log(new AuditEntry(
            action: 'export.enrollments',
            metadata: ['row_count' => count($rows), 'format' => 'csv'],
        ));

        return new ExportResponse(
            url: $exported->url,
            expiresAt: $exported->expiresAt->format(DateTimeInterface::ATOM),
            rowCount: count($rows),
            format: 'csv',
        );
    }
}
