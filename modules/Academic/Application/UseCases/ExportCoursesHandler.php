<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeInterface;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\ExportCoursesCommand;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Foundation\Application\Responses\ExportResponse;
use Modules\Foundation\Infrastructure\Export\CsvWriter;
use Modules\Foundation\Infrastructure\Export\ExportFileWriter;

final readonly class ExportCoursesHandler
{
    public function __construct(
        private CourseRepository $courses,
        private ExportFileWriter $exportFileWriter,
        private CsvWriter $csvWriter,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(ExportCoursesCommand $command): ExportResponse
    {
        $courses = $this->courses->all();

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
            $courses,
        );

        $csv = $this->csvWriter->toString(
            ['id', 'code', 'title', 'description', 'objectives', 'prerequisites', 'modality', 'duration_hours', 'status'],
            $rows,
        );

        $storagePath = sprintf('exports/courses/%s.csv', (string) Str::uuid());
        $exported = $this->exportFileWriter->write($storagePath, $csv);

        $this->auditLogger->log(new AuditEntry(
            action: 'export.courses',
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
