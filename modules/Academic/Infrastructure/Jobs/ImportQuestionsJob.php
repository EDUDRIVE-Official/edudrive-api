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
use JsonException;
use Modules\Academic\Application\Commands\CreateQuestionCommand;
use Modules\Academic\Application\UseCases\CreateQuestionHandler;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Throwable;

final class ImportQuestionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public readonly ?string $correlationId;

    /** @param list<array{competency_code: string, type: string, prompt: string, score: string, response: string, options: string, explanation: string, media: string, source_kind: string, source_reference: string, license_categories: string}> $rows */
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

    public function handle(AsyncJobRepository $jobs, QuestionRepository $questions, CompetencyRepository $competencies): void
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
                $results[] = $this->importRow($questions, $competencies, $rowNumber, $row);
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
     * @param  array{competency_code: string, type: string, prompt: string, score: string, response: string, options: string, explanation: string, media: string, source_kind: string, source_reference: string, license_categories: string}  $row
     * @return array{row: int, created: bool, question_id: string}
     */
    private function importRow(QuestionRepository $questions, CompetencyRepository $competencies, int $rowNumber, array $row): array
    {
        $competencyCode = trim($row['competency_code']);
        $type = trim($row['type']);
        $prompt = trim($row['prompt']);
        $scoreValue = trim($row['score']);

        if ($competencyCode === '' || $type === '' || $prompt === '' || $scoreValue === '') {
            throw new InvalidArgumentException('Fila incompleta: se requieren competency_code, type, prompt y score.');
        }

        if (! ctype_digit($scoreValue)) {
            throw new InvalidArgumentException('score debe ser un entero.');
        }

        $competency = $competencies->findByCode(CompetencyCode::fromString($competencyCode));
        if ($competency === null) {
            throw new InvalidArgumentException("No se encontró la competencia con código \"{$competencyCode}\".");
        }

        $explanation = trim($row['explanation']);
        $sourceKind = trim($row['source_kind']);
        $sourceReference = trim($row['source_reference']);

        $questionResponse = (new CreateQuestionHandler($questions, $competencies))->handle(new CreateQuestionCommand(
            competencyId: $competency->id()->value(),
            type: $type,
            prompt: $prompt,
            score: (int) $scoreValue,
            response: $this->decodeJsonObject($row['response']),
            options: $this->normalizeOptions($this->decodeJsonArray($row['options'])),
            explanation: $explanation === '' ? null : $explanation,
            media: $this->normalizeMedia($this->decodeJsonArray($row['media'])),
            sourceKind: $sourceKind === '' ? 'custom' : $sourceKind,
            sourceReference: $sourceReference === '' ? null : $sourceReference,
            licenseCategories: $this->normalizeLicenseCategories($this->decodeJsonArray($row['license_categories'])),
        ));

        return [
            'row' => $rowNumber,
            'created' => true,
            'question_id' => $questionResponse->id,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeJsonObject(string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('response es obligatorio y debe ser JSON válido.');
        }

        $decoded = $this->decodeJson($trimmed);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('response debe decodificar a un objeto JSON.');
        }

        return $decoded;
    }

    /** @return list<mixed> */
    private function decodeJsonArray(string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = $this->decodeJson($trimmed);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Se esperaba una lista JSON válida.');
        }

        return array_values($decoded);
    }

    private function decodeJson(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('El valor no es JSON válido.');
        }
    }

    /**
     * @param  list<mixed>  $options
     * @return list<array{refId: string, label: string, side?: string|null}>
     */
    private function normalizeOptions(array $options): array
    {
        return array_map(static function (mixed $option): array {
            if (! is_array($option) || ! isset($option['ref_id'], $option['label'])) {
                throw new InvalidArgumentException('Cada opción requiere ref_id y label.');
            }

            return [
                'refId' => (string) $option['ref_id'],
                'label' => (string) $option['label'],
                'side' => isset($option['side']) ? (string) $option['side'] : null,
            ];
        }, $options);
    }

    /**
     * @param  list<mixed>  $media
     * @return list<array{type: string, url: string}>
     */
    private function normalizeMedia(array $media): array
    {
        return array_map(static function (mixed $item): array {
            if (! is_array($item) || ! isset($item['type'], $item['url'])) {
                throw new InvalidArgumentException('Cada elemento de media requiere type y url.');
            }

            return [
                'type' => (string) $item['type'],
                'url' => (string) $item['url'],
            ];
        }, $media);
    }

    /**
     * @param  list<mixed>  $categories
     * @return list<string>
     */
    private function normalizeLicenseCategories(array $categories): array
    {
        return array_map(static fn (mixed $category): string => (string) $category, $categories);
    }

    public function failed(?Throwable $exception): void
    {
        $jobs = app(AsyncJobRepository::class);
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->fail($exception?->getMessage() ?? 'Error desconocido al importar preguntas.', new DateTimeImmutable('now'));
        $jobs->save($job);

        Log::warning('Fallo la importacion asincrona de preguntas.', [
            'async_job_id' => $this->asyncJobId,
            'correlation_id' => $this->correlationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
