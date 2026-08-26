<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\CreateExamCommand;
use Modules\Academic\Application\Commands\DeleteExamCommand;
use Modules\Academic\Application\Commands\UpdateExamCommand;
use Modules\Academic\Application\Queries\GetExamQuery;
use Modules\Academic\Application\Queries\ListExamsQuery;
use Modules\Academic\Application\Responses\ExamListItemResponse;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Presentation\Http\Requests\CreateExamRequest;
use Modules\Academic\Presentation\Http\Requests\UpdateExamRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class ExamController
{
    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $courseId = $request->query('course_id');
        $result = $queryBus->ask(new ListExamsQuery(
            courseId: is_string($courseId) && $courseId !== '' ? $courseId : null,
        ));
        assert(is_array($result));

        /** @var list<ExamListItemResponse> $result */
        return response()->json(['data' => array_map(
            static fn (ExamListItemResponse $exam): array => $exam->toArray(),
            $result,
        )]);
    }

    public function store(CreateExamRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateExamCommand(
            courseId: (string) $data['course_id'],
            title: (string) $data['title'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            durationMinutes: isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
            maxAttempts: (int) ($data['max_attempts'] ?? 1),
            passingScore: (int) ($data['passing_score'] ?? 60),
            shuffleQuestions: $request->boolean('shuffle_questions', false),
            feedbackMode: (string) ($data['feedback_mode'] ?? 'none'),
            kind: (string) ($data['kind'] ?? 'standard'),
            licenseCategory: isset($data['license_category']) ? (string) $data['license_category'] : null,
            allowPartialCredit: $request->boolean('allow_partial_credit', false),
            applyPenalties: $request->boolean('apply_penalties', false),
            questions: self::normalizeQuestions($data['questions'] ?? []),
        ));
        assert($result instanceof ExamResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function show(string $examId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetExamQuery(examId: $examId));
        assert($result instanceof ExamResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(string $examId, UpdateExamRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new UpdateExamCommand(
            examId: $examId,
            title: (string) $data['title'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            durationMinutes: isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
            maxAttempts: (int) ($data['max_attempts'] ?? 1),
            passingScore: (int) ($data['passing_score'] ?? 60),
            shuffleQuestions: $request->boolean('shuffle_questions', false),
            feedbackMode: (string) ($data['feedback_mode'] ?? 'none'),
            kind: (string) ($data['kind'] ?? 'standard'),
            licenseCategory: isset($data['license_category']) ? (string) $data['license_category'] : null,
            allowPartialCredit: $request->boolean('allow_partial_credit', false),
            applyPenalties: $request->boolean('apply_penalties', false),
            questions: self::normalizeQuestions($data['questions'] ?? []),
        ));
        assert($result instanceof ExamResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function destroy(string $examId, CommandBus $commandBus): JsonResponse
    {
        $commandBus->dispatch(new DeleteExamCommand(examId: $examId));

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return list<array{questionId: string, points: int}>
     */
    private static function normalizeQuestions(array $questions): array
    {
        return array_map(static fn (array $question): array => [
            'questionId' => (string) $question['question_id'],
            'points' => (int) $question['points'],
        ], $questions);
    }
}
