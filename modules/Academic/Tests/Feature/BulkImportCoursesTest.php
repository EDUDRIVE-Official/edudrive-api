<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;

use function Pest\Laravel\assertDatabaseHas;

use Tests\TestCase;

function academicBulkImportCsvFile(string $content, string $name = 'cursos.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

it('importa cursos en lote con el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $codeA = 'IMP-'.strtoupper((string) Str::random(4));
    $codeB = 'IMP-'.strtoupper((string) Str::random(4));
    $csv = "code,title,description,objectives,prerequisites,modality,duration_hours\n"
        ."{$codeA},Curso importado A,,,,,\n"
        ."{$codeB},Curso importado B,Descripción B,,,virtual,30\n";

    $response = $this->post('/api/v1/academic/courses/import', ['file' => academicBulkImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.type', 'import.courses');

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.result.total', 2)
        ->assertJsonPath('data.result.created', 2)
        ->assertJsonPath('data.result.failed', 0);

    assertDatabaseHas('academic_courses', ['code' => $codeA, 'title' => 'Curso importado A']);
    assertDatabaseHas('academic_courses', ['code' => $codeB, 'modality' => 'virtual', 'duration_hours' => 30]);
});

it('reporta una fila fallida por codigo de curso duplicado sin detener el resto del lote', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $existingCode = 'IMP-'.strtoupper((string) Str::random(4));
    $newCode = 'IMP-'.strtoupper((string) Str::random(4));
    $csv = "code,title,description,objectives,prerequisites,modality,duration_hours\n"
        ."{$existingCode},Curso original,,,,,\n";
    $this->post('/api/v1/academic/courses/import', ['file' => academicBulkImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202);

    $csvWithDuplicate = "code,title,description,objectives,prerequisites,modality,duration_hours\n"
        ."{$existingCode},Curso duplicado,,,,,\n"
        ."{$newCode},Curso nuevo,,,,,\n";

    $response = $this->post('/api/v1/academic/courses/import', ['file' => academicBulkImportCsvFile($csvWithDuplicate)], ['Accept' => 'application/json'])
        ->assertStatus(202);

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.result.total', 2)
        ->assertJsonPath('data.result.created', 1)
        ->assertJsonPath('data.result.failed', 1)
        ->assertJsonPath('data.result.results.0.error_code', 'COURSE_CODE_ALREADY_EXISTS')
        ->assertJsonPath('data.result.results.1.created', true);
});

it('reporta una fila fallida por modalidad invalida', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $csv = "code,title,description,objectives,prerequisites,modality,duration_hours\n"
        .'IMP-'.strtoupper((string) Str::random(4)).",Curso con modalidad invalida,,,,no-existe,\n";

    $response = $this->post('/api/v1/academic/courses/import', ['file' => academicBulkImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202);

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.result.failed', 1)
        ->assertJsonPath('data.result.results.0.error_code', 'IMPORT_ROW_INVALID');
});

it('rechaza importar cursos sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $csv = "code,title,description,objectives,prerequisites,modality,duration_hours\nIMP-0001,Curso,,,,,\n";

    $this->post('/api/v1/academic/courses/import', ['file' => academicBulkImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertForbidden();
});

it('requiere autenticacion para importar cursos en lote', function (): void {
    /** @var TestCase $this */
    $csv = "code,title,description,objectives,prerequisites,modality,duration_hours\nIMP-0001,Curso,,,,,\n";

    $this->post('/api/v1/academic/courses/import', ['file' => academicBulkImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertUnauthorized();
});
