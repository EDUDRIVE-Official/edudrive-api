<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\BulkImportCoursesCommand;
use Modules\Academic\Application\Responses\BulkImportCoursesResponse;
use Modules\Academic\Application\UseCases\BulkImportCoursesHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

/** @return array{code: string, title: string, description: string, objectives: string, prerequisites: string, modality: string, duration_hours: string} */
function bulkCourseRow(string $code, string $title = 'Curso importado'): array
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

it('importa cursos en lote y devuelve resultado parcial por fila', function (): void {
    $handler = new BulkImportCoursesHandler(app(CourseRepository::class));

    $response = $handler->handle(new BulkImportCoursesCommand(rows: [
        bulkCourseRow('IMP-'.strtoupper((string) Str::random(4))),
        array_merge(
            bulkCourseRow('IMP-'.strtoupper((string) Str::random(4)), 'Curso con modalidad virtual'),
            ['modality' => 'virtual', 'duration_hours' => '40'],
        ),
    ]));

    expect($response)->toBeInstanceOf(BulkImportCoursesResponse::class)
        ->and($response->total)->toBe(2)
        ->and($response->created)->toBe(2)
        ->and($response->failed)->toBe(0)
        ->and($response->results[0]['created'])->toBeTrue()
        ->and($response->results[1]['created'])->toBeTrue();
});

it('reporta una fila fallida por codigo de curso duplicado', function (): void {
    $code = CourseCode::fromString('IMP-'.strtoupper((string) Str::random(4)));
    app(CourseRepository::class)->save(Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: $code,
        title: CourseTitle::fromString('Curso ya existente'),
    ));
    $handler = new BulkImportCoursesHandler(app(CourseRepository::class));

    $response = $handler->handle(new BulkImportCoursesCommand(rows: [
        bulkCourseRow($code->value()),
    ]));

    expect($response->failed)->toBe(1)
        ->and($response->results[0]['created'])->toBeFalse()
        ->and($response->results[0]['error_code'])->toBe('COURSE_CODE_ALREADY_EXISTS');
});

it('reporta una fila fallida por campos incompletos', function (): void {
    $handler = new BulkImportCoursesHandler(app(CourseRepository::class));

    $response = $handler->handle(new BulkImportCoursesCommand(rows: [
        bulkCourseRow(''),
    ]));

    expect($response->failed)->toBe(1)
        ->and($response->results[0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});

it('reporta una fila fallida por modalidad invalida', function (): void {
    $handler = new BulkImportCoursesHandler(app(CourseRepository::class));

    $row = bulkCourseRow('IMP-'.strtoupper((string) Str::random(4)));
    $row['modality'] = 'modalidad_inexistente';

    $response = $handler->handle(new BulkImportCoursesCommand(rows: [$row]));

    expect($response->failed)->toBe(1)
        ->and($response->results[0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});
