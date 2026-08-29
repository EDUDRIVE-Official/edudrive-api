<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Authorization\Domain\Enums\Role;

use function Pest\Laravel\assertDatabaseCount;

use Tests\TestCase;

function academicBulkQuestionImportCsvFile(string $content, string $name = 'preguntas.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function persistedFeatureQuestionCompetencyCode(): string
{
    $code = CompetencyCode::fromString('BQF-'.strtoupper((string) Str::random(4)));
    app(CompetencyRepository::class)->save(Competency::create(
        CompetencyId::fromString((string) Str::uuid()),
        $code,
        'Competencia feature para importacion',
        'Usada unicamente en pruebas HTTP de importacion masiva de preguntas.',
        CompetencyCategory::RoadRules,
        MasteryLevel::Foundation,
    ));

    return $code->value();
}

function csvJsonCell(mixed $value): string
{
    return '"'.str_replace('"', '""', json_encode($value, JSON_THROW_ON_ERROR)).'"';
}

it('importa preguntas en lote con el permiso questions.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $competencyCode = persistedFeatureQuestionCompetencyCode();

    $response = csvJsonCell(['optionId' => 'opt-a']);
    $options = csvJsonCell([
        ['ref_id' => 'opt-a', 'label' => '50 km/h'],
        ['ref_id' => 'opt-b', 'label' => '80 km/h'],
    ]);

    $csv = "competency_code,type,prompt,score,response,options,explanation,media,source_kind,source_reference,license_categories\n"
        ."{$competencyCode},single_choice,¿Cuál es la velocidad máxima?,1,{$response},{$options},,,,,\n";

    $response = $this->post('/api/v1/academic/questions/import', ['file' => academicBulkQuestionImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.type', 'import.questions');

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.result.total', 1)
        ->assertJsonPath('data.result.created', 1)
        ->assertJsonPath('data.result.failed', 0);

    assertDatabaseCount('academic_questions', 1);
});

it('reporta una fila fallida por competencia inexistente sin detener el resto del lote', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $competencyCode = persistedFeatureQuestionCompetencyCode();
    $response = csvJsonCell(['optionId' => 'opt-a']);
    $options = csvJsonCell([
        ['ref_id' => 'opt-a', 'label' => 'A'],
        ['ref_id' => 'opt-b', 'label' => 'B'],
    ]);

    $csv = "competency_code,type,prompt,score,response,options,explanation,media,source_kind,source_reference,license_categories\n"
        ."CODIGO-INEXISTENTE,single_choice,Pregunta invalida,1,{$response},{$options},,,,,\n"
        ."{$competencyCode},single_choice,Pregunta valida,1,{$response},{$options},,,,,\n";

    $response = $this->post('/api/v1/academic/questions/import', ['file' => academicBulkQuestionImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertStatus(202);

    $this->getJson('/api/v1/async-jobs/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.result.total', 2)
        ->assertJsonPath('data.result.created', 1)
        ->assertJsonPath('data.result.failed', 1)
        ->assertJsonPath('data.result.results.0.error_code', 'IMPORT_ROW_INVALID')
        ->assertJsonPath('data.result.results.1.created', true);
});

it('rechaza importar preguntas sin el permiso questions.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $csv = "competency_code,type,prompt,score,response,options,explanation,media,source_kind,source_reference,license_categories\nX,single_choice,Prompt,1,{},[],,,,,\n";

    $this->post('/api/v1/academic/questions/import', ['file' => academicBulkQuestionImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertForbidden();
});

it('requiere autenticacion para importar preguntas en lote', function (): void {
    /** @var TestCase $this */
    $csv = "competency_code,type,prompt,score,response,options,explanation,media,source_kind,source_reference,license_categories\nX,single_choice,Prompt,1,{},[],,,,,\n";

    $this->post('/api/v1/academic/questions/import', ['file' => academicBulkQuestionImportCsvFile($csv)], ['Accept' => 'application/json'])
        ->assertUnauthorized();
});
