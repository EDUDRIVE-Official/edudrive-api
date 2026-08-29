<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use Modules\Academic\Application\Commands\BulkImportQuestionsCommand;
use Modules\Academic\Application\Commands\CreateQuestionCommand;
use Modules\Academic\Application\Commands\DeleteQuestionCommand;
use Modules\Academic\Application\Commands\UpdateQuestionCommand;
use Modules\Academic\Application\Queries\GetQuestionQuery;
use Modules\Academic\Application\Queries\ListQuestionsQuery;
use Modules\Academic\Application\Responses\QuestionListItemResponse;
use Modules\Academic\Application\Responses\QuestionResponse;
use Modules\Academic\Presentation\Http\Requests\BulkImportQuestionsRequest;
use Modules\Academic\Presentation\Http\Requests\CreateQuestionRequest;
use Modules\Academic\Presentation\Http\Requests\UpdateQuestionRequest;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class QuestionController
{
    private const int MAX_IMPORT_ROWS = 500;

    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $competencyId = $request->query('competency_id');
        $result = $queryBus->ask(new ListQuestionsQuery(
            competencyId: is_string($competencyId) && $competencyId !== '' ? $competencyId : null,
        ));
        assert(is_array($result));

        /** @var list<QuestionListItemResponse> $result */
        return response()->json(['data' => array_map(
            static fn (QuestionListItemResponse $question): array => $question->toArray(),
            $result,
        )]);
    }

    public function store(CreateQuestionRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateQuestionCommand(
            competencyId: (string) $data['competency_id'],
            type: (string) $data['type'],
            prompt: (string) $data['prompt'],
            score: (int) $data['score'],
            response: $data['response'],
            options: self::normalizeOptions($data['options'] ?? []),
            explanation: isset($data['explanation']) ? (string) $data['explanation'] : null,
            media: $data['media'] ?? [],
            sourceKind: isset($data['source_kind']) ? (string) $data['source_kind'] : 'custom',
            sourceReference: isset($data['source_reference']) ? (string) $data['source_reference'] : null,
            licenseCategories: self::normalizeLicenseCategories($data['license_categories'] ?? []),
        ));
        assert($result instanceof QuestionResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function show(string $questionId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetQuestionQuery(questionId: $questionId));
        assert($result instanceof QuestionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(string $questionId, UpdateQuestionRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new UpdateQuestionCommand(
            questionId: $questionId,
            type: (string) $data['type'],
            prompt: (string) $data['prompt'],
            score: (int) $data['score'],
            response: $data['response'],
            options: self::normalizeOptions($data['options'] ?? []),
            explanation: isset($data['explanation']) ? (string) $data['explanation'] : null,
            media: $data['media'] ?? [],
            sourceKind: isset($data['source_kind']) ? (string) $data['source_kind'] : 'custom',
            sourceReference: isset($data['source_reference']) ? (string) $data['source_reference'] : null,
            licenseCategories: self::normalizeLicenseCategories($data['license_categories'] ?? []),
        ));
        assert($result instanceof QuestionResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function destroy(string $questionId, CommandBus $commandBus): JsonResponse
    {
        $commandBus->dispatch(new DeleteQuestionCommand(questionId: $questionId));

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function bulkImport(BulkImportQuestionsRequest $request, CommandBus $commandBus): JsonResponse
    {
        $file = $request->file('file');
        assert($file !== null);

        $csv = Reader::createFromPath((string) $file->getRealPath());
        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            $rows[] = [
                'competency_code' => (string) ($record['competency_code'] ?? ''),
                'type' => (string) ($record['type'] ?? ''),
                'prompt' => (string) ($record['prompt'] ?? ''),
                'score' => (string) ($record['score'] ?? ''),
                'response' => (string) ($record['response'] ?? ''),
                'options' => (string) ($record['options'] ?? ''),
                'explanation' => (string) ($record['explanation'] ?? ''),
                'media' => (string) ($record['media'] ?? ''),
                'source_kind' => (string) ($record['source_kind'] ?? ''),
                'source_reference' => (string) ($record['source_reference'] ?? ''),
                'license_categories' => (string) ($record['license_categories'] ?? ''),
            ];
        }

        if (count($rows) > self::MAX_IMPORT_ROWS) {
            throw ValidationException::withMessages([
                'file' => [sprintf('El archivo no puede contener más de %d filas.', self::MAX_IMPORT_ROWS)],
            ]);
        }

        $result = $commandBus->dispatch(new BulkImportQuestionsCommand(
            rows: $rows,
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AsyncJobResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_ACCEPTED);
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<array{refId: string, label: string, side?: string|null}>
     */
    private static function normalizeOptions(array $options): array
    {
        return array_map(static fn (array $option): array => [
            'refId' => (string) $option['ref_id'],
            'label' => (string) $option['label'],
            'side' => isset($option['side']) ? (string) $option['side'] : null,
        ], $options);
    }

    /**
     * @param  list<mixed>  $licenseCategories
     * @return list<string>
     */
    private static function normalizeLicenseCategories(array $licenseCategories): array
    {
        return array_map(static fn (mixed $category): string => (string) $category, $licenseCategories);
    }
}
