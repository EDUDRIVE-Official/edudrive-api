<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\BulkImportQuestionsCommand;
use Modules\Academic\Application\Responses\BulkImportQuestionsResponse;
use Modules\Academic\Application\UseCases\BulkImportQuestionsHandler;
use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

function persistedBulkQuestionCompetencyCode(): string
{
    $code = CompetencyCode::fromString('BQ-'.strtoupper((string) Str::random(4)));
    app(CompetencyRepository::class)->save(Competency::create(
        CompetencyId::fromString((string) Str::uuid()),
        $code,
        'Competencia para importacion masiva',
        'Usada unicamente en pruebas de importacion masiva de preguntas.',
        CompetencyCategory::RoadRules,
        MasteryLevel::Foundation,
    ));

    return $code->value();
}

/** @return array{competency_code: string, type: string, prompt: string, score: string, response: string, options: string, explanation: string, media: string, source_kind: string, source_reference: string, license_categories: string} */
function bulkQuestionRow(string $competencyCode, string $prompt = '¿Cuál es la velocidad máxima en ciudad?'): array
{
    return [
        'competency_code' => $competencyCode,
        'type' => 'single_choice',
        'prompt' => $prompt,
        'score' => '1',
        'response' => json_encode(['optionId' => 'opt-a'], JSON_THROW_ON_ERROR),
        'options' => json_encode([
            ['ref_id' => 'opt-a', 'label' => '50 km/h'],
            ['ref_id' => 'opt-b', 'label' => '80 km/h'],
        ], JSON_THROW_ON_ERROR),
        'explanation' => '',
        'media' => '',
        'source_kind' => '',
        'source_reference' => '',
        'license_categories' => '',
    ];
}

it('importa preguntas en lote resolviendo la competencia por codigo', function (): void {
    $handler = new BulkImportQuestionsHandler(app(QuestionRepository::class), app(CompetencyRepository::class));
    $competencyCode = persistedBulkQuestionCompetencyCode();

    $response = $handler->handle(new BulkImportQuestionsCommand(rows: [
        bulkQuestionRow($competencyCode),
    ]));

    expect($response)->toBeInstanceOf(BulkImportQuestionsResponse::class)
        ->and($response->total)->toBe(1)
        ->and($response->created)->toBe(1)
        ->and($response->failed)->toBe(0)
        ->and($response->results[0]['created'])->toBeTrue();
});

it('reporta una fila fallida por competencia inexistente', function (): void {
    $handler = new BulkImportQuestionsHandler(app(QuestionRepository::class), app(CompetencyRepository::class));

    $response = $handler->handle(new BulkImportQuestionsCommand(rows: [
        bulkQuestionRow('CODIGO-INEXISTENTE'),
    ]));

    expect($response->failed)->toBe(1)
        ->and($response->results[0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});

it('reporta una fila fallida por json invalido en response', function (): void {
    $handler = new BulkImportQuestionsHandler(app(QuestionRepository::class), app(CompetencyRepository::class));
    $competencyCode = persistedBulkQuestionCompetencyCode();

    $row = bulkQuestionRow($competencyCode);
    $row['response'] = '{invalido';

    $response = $handler->handle(new BulkImportQuestionsCommand(rows: [$row]));

    expect($response->failed)->toBe(1)
        ->and($response->results[0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});

it('reporta una fila fallida por campos incompletos', function (): void {
    $handler = new BulkImportQuestionsHandler(app(QuestionRepository::class), app(CompetencyRepository::class));

    $response = $handler->handle(new BulkImportQuestionsCommand(rows: [
        bulkQuestionRow(''),
    ]));

    expect($response->failed)->toBe(1)
        ->and($response->results[0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});

it('importa varias filas devolviendo exito parcial', function (): void {
    $handler = new BulkImportQuestionsHandler(app(QuestionRepository::class), app(CompetencyRepository::class));
    $competencyCode = persistedBulkQuestionCompetencyCode();

    $response = $handler->handle(new BulkImportQuestionsCommand(rows: [
        bulkQuestionRow($competencyCode, 'Primera pregunta'),
        bulkQuestionRow('CODIGO-INEXISTENTE', 'Segunda pregunta'),
        bulkQuestionRow($competencyCode, 'Tercera pregunta'),
    ]));

    expect($response->total)->toBe(3)
        ->and($response->created)->toBe(2)
        ->and($response->failed)->toBe(1)
        ->and($response->results[1]['created'])->toBeFalse();
});
