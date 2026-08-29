<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Jobs;

use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\UseCases\CreateCourseHandler;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Throwable;

final class ImportCoursesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public readonly ?string $correlationId;

    /** @param list<array{code: string, title: string, description: string, objectives: string, prerequisites: string, modality: string, duration_hours: string}> $rows */
    public function __construct(
        public readonly string $asyncJobId,
        public readonly array $rows,
    ) {
        $this->correlationId = Context::get('correlation_id');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(AsyncJobRepository $jobs, CourseRepository $courses): void
    {
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->start(new DateTimeImmutable('now'));
        $jobs->save($job);

        $created = 0;
        $failed = 0;
        $results = [];

        foreach ($this->rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $results[] = $this->importRow($courses, $rowNumber, $row);
                $created++;
            } catch (DomainException $e) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => $e->errorCode()];
            } catch (Throwable) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => 'IMPORT_ROW_INVALID'];
            }
        }

        $job->complete([
            'total' => count($this->rows),
            'created' => $created,
            'failed' => $failed,
            'results' => $results,
        ], new DateTimeImmutable('now'));
        $jobs->save($job);
    }

    /**
     * @param  array{code: string, title: string, description: string, objectives: string, prerequisites: string, modality: string, duration_hours: string}  $row
     * @return array{row: int, created: bool, course_id: string, code: string}
     */
    private function importRow(CourseRepository $courses, int $rowNumber, array $row): array
    {
        $code = trim($row['code']);
        $title = trim($row['title']);

        if ($code === '' || $title === '') {
            throw new InvalidArgumentException('Fila incompleta: se requieren code y title.');
        }

        $durationHours = trim($row['duration_hours']);
        if ($durationHours !== '' && ! ctype_digit($durationHours)) {
            throw new InvalidArgumentException('duration_hours debe ser un entero.');
        }

        $response = (new CreateCourseHandler($courses))->handle(new CreateCourseCommand(
            code: $code,
            title: $title,
            description: $this->nullableString($row['description']),
            objectives: $this->nullableString($row['objectives']),
            prerequisites: $this->nullableString($row['prerequisites']),
            modality: $this->nullableString($row['modality']),
            durationHours: $durationHours === '' ? null : (int) $durationHours,
        ));

        return [
            'row' => $rowNumber,
            'created' => true,
            'course_id' => $response->id,
            'code' => $response->code,
        ];
    }

    private function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function failed(?Throwable $exception): void
    {
        $jobs = app(AsyncJobRepository::class);
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->fail($exception?->getMessage() ?? 'Error desconocido al importar cursos.', new DateTimeImmutable('now'));
        $jobs->save($job);

        Log::warning('Fallo la importacion asincrona de cursos.', [
            'async_job_id' => $this->asyncJobId,
            'correlation_id' => $this->correlationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
