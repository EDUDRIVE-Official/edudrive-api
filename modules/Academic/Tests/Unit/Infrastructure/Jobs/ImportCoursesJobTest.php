<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Infrastructure\Jobs\ImportCoursesJob;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

uses(RefreshDatabase::class);

final class InMemoryAsyncJobRepositoryForCoursesImportJob implements AsyncJobRepository
{
    /** @var array<string, AsyncJob> */
    public array $items = [];

    public function save(AsyncJob $job): void
    {
        $this->items[$job->id()->value()] = $job;
    }

    public function findById(AsyncJobId $id): ?AsyncJob
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AsyncJob> */
    public function allCompletedOrFailedBefore(DateTimeImmutable $threshold): array
    {
        return [];
    }

    public function delete(AsyncJobId $id): void
    {
        unset($this->items[$id->value()]);
    }
}

/** @return array{code: string, title: string, description: string, objectives: string, prerequisites: string, modality: string, duration_hours: string} */
function bulkCourseJobRow(string $code, string $title = 'Curso importado'): array
{
    return [
        'code' => $code,
        'title' => $title,
        'description' => '',
        'objectives' => '',
        'prerequisites' => '',
        'modality' => '',
        'duration_hours' => '',
    ];
}

it('importa cursos en lote y completa el trabajo con resultado parcial por fila', function (): void {
    $jobs = new InMemoryAsyncJobRepositoryForCoursesImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.courses', 'user-1'));

    (new ImportCoursesJob($asyncJobId->value(), [
        bulkCourseJobRow('IMP-'.strtoupper((string) Str::random(4))),
        array_merge(
            bulkCourseJobRow('IMP-'.strtoupper((string) Str::random(4)), 'Curso con modalidad virtual'),
            ['modality' => 'virtual', 'duration_hours' => '40'],
        ),
    ]))->handle($jobs, app(CourseRepository::class));

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->status())->toBe(AsyncJobStatus::Completed)
        ->and($completed?->result()['total'])->toBe(2)
        ->and($completed?->result()['created'])->toBe(2)
        ->and($completed?->result()['failed'])->toBe(0);
});

it('reporta una fila fallida por codigo de curso duplicado sin detener el resto del lote', function (): void {
    $code = CourseCode::fromString('IMP-'.strtoupper((string) Str::random(4)));
    app(CourseRepository::class)->save(Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: $code,
        title: CourseTitle::fromString('Curso ya existente'),
    ));
    $jobs = new InMemoryAsyncJobRepositoryForCoursesImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.courses', 'user-1'));

    (new ImportCoursesJob($asyncJobId->value(), [bulkCourseJobRow($code->value())]))
        ->handle($jobs, app(CourseRepository::class));

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['failed'])->toBe(1)
        ->and($completed?->result()['results'][0]['error_code'])->toBe('COURSE_CODE_ALREADY_EXISTS');
});

it('reporta una fila fallida por campos incompletos', function (): void {
    $jobs = new InMemoryAsyncJobRepositoryForCoursesImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.courses', 'user-1'));

    (new ImportCoursesJob($asyncJobId->value(), [bulkCourseJobRow('')]))
        ->handle($jobs, app(CourseRepository::class));

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['failed'])->toBe(1)
        ->and($completed?->result()['results'][0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});

it('reporta una fila fallida por modalidad invalida', function (): void {
    $jobs = new InMemoryAsyncJobRepositoryForCoursesImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.courses', 'user-1'));
    $row = bulkCourseJobRow('IMP-'.strtoupper((string) Str::random(4)));
    $row['modality'] = 'modalidad_inexistente';

    (new ImportCoursesJob($asyncJobId->value(), [$row]))->handle($jobs, app(CourseRepository::class));

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['failed'])->toBe(1)
        ->and($completed?->result()['results'][0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});
