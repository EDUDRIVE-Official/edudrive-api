<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Infrastructure\Jobs\ImportQuestionsJob;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

uses(RefreshDatabase::class);

final class InMemoryAsyncJobRepositoryForQuestionsImportJob implements AsyncJobRepository
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

function persistedBulkQuestionJobCompetencyCode(): string
{
    $code = CompetencyCode::fromString('BQJ-'.strtoupper((string) Str::random(4)));
    app(CompetencyRepository::class)->save(Competency::create(
        CompetencyId::fromString((string) Str::uuid()),
        $code,
        'Competencia para importacion masiva asincrona',
        'Usada unicamente en pruebas de importacion masiva asincrona de preguntas.',
        CompetencyCategory::RoadRules,
        MasteryLevel::Foundation,
    ));

    return $code->value();
}

/** @return array{competency_code: string, type: string, prompt: string, score: string, response: string, options: string, explanation: string, media: string, source_kind: string, source_reference: string, license_categories: string} */
function bulkQuestionJobRow(string $competencyCode, string $prompt = '¿Cuál es la velocidad máxima en ciudad?'): array
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
    $jobs = new InMemoryAsyncJobRepositoryForQuestionsImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.questions', 'user-1'));
    $competencyCode = persistedBulkQuestionJobCompetencyCode();

    (new ImportQuestionsJob($asyncJobId->value(), [bulkQuestionJobRow($competencyCode)]))
        ->handle($jobs, app(QuestionRepository::class), app(CompetencyRepository::class));

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->status())->toBe(AsyncJobStatus::Completed)
        ->and($completed?->result()['total'])->toBe(1)
        ->and($completed?->result()['created'])->toBe(1)
        ->and($completed?->result()['failed'])->toBe(0);
});

it('reporta una fila fallida por competencia inexistente sin detener el resto del lote', function (): void {
    $jobs = new InMemoryAsyncJobRepositoryForQuestionsImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.questions', 'user-1'));
    $competencyCode = persistedBulkQuestionJobCompetencyCode();

    (new ImportQuestionsJob($asyncJobId->value(), [
        bulkQuestionJobRow('CODIGO-INEXISTENTE', 'Pregunta invalida'),
        bulkQuestionJobRow($competencyCode, 'Pregunta valida'),
    ]))->handle($jobs, app(QuestionRepository::class), app(CompetencyRepository::class));

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['total'])->toBe(2)
        ->and($completed?->result()['created'])->toBe(1)
        ->and($completed?->result()['failed'])->toBe(1)
        ->and($completed?->result()['results'][0]['error_code'])->toBe('IMPORT_ROW_INVALID')
        ->and($completed?->result()['results'][1]['created'])->toBeTrue();
});

it('reporta una fila fallida por json invalido en response', function (): void {
    $jobs = new InMemoryAsyncJobRepositoryForQuestionsImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.questions', 'user-1'));
    $competencyCode = persistedBulkQuestionJobCompetencyCode();
    $row = bulkQuestionJobRow($competencyCode);
    $row['response'] = '{invalido';

    (new ImportQuestionsJob($asyncJobId->value(), [$row]))
        ->handle($jobs, app(QuestionRepository::class), app(CompetencyRepository::class));

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['failed'])->toBe(1)
        ->and($completed?->result()['results'][0]['error_code'])->toBe('IMPORT_ROW_INVALID');
});
