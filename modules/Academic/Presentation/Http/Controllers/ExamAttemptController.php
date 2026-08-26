<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\CancelExamAttemptCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\Responses\ExamAttemptListItemResponse;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\QuestionResponseFactory;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Presentation\Http\Requests\AnswerAttemptQuestionRequest;
use Modules\Academic\Presentation\Http\Requests\StartExamAttemptRequest;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class ExamAttemptController
{
    public function start(StartExamAttemptRequest $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new StartExamAttemptCommand(
            examId: (string) $request->validated('exam_id'),
            userId: (string) $user->getAuthIdentifier(),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function answer(string $attemptId, int $position, AnswerAttemptQuestionRequest $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $question = $this->attemptQuestion($request, $attemptId, $position);
        $response = QuestionResponseFactory::fromPayload($question->type()->value, (array) $request->validated('response'));

        $result = $commandBus->dispatch(new AnswerAttemptQuestionCommand(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
            position: $position,
            response: $response,
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function submit(string $attemptId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new SubmitExamAttemptCommand(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function cancel(string $attemptId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new CancelExamAttemptCommand(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function show(string $attemptId, Request $request, QueryBus $queryBus, PermissionChecker $permissionChecker): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetExamAttemptQuery(
            attemptId: $attemptId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewExamAttempts),
        ));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $examId = $request->query('exam_id');
        $userId = $request->query('user_id');
        $status = $request->query('status');

        $result = $queryBus->ask(new ListExamAttemptsQuery(
            examId: is_string($examId) && $examId !== '' ? $examId : null,
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            status: is_string($status) && $status !== '' ? $status : null,
        ));
        assert(is_array($result));

        /** @var list<ExamAttemptListItemResponse> $result */
        return response()->json(['data' => array_map(
            static fn (ExamAttemptListItemResponse $attempt): array => $attempt->toArray(),
            $result,
        )]);
    }

    private function attemptQuestion(Request $request, string $attemptId, int $position): Question
    {
        $user = self::authenticatedUser($request);
        $attempt = app(ExamAttemptRepository::class)
            ->findById(ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== (string) $user->getAuthIdentifier()) {
            throw ExamAttemptNotFound::withId($attemptId);
        }

        $question = $attempt->questionAt($position);
        if ($question === null) {
            throw InvalidExamAttempt::create();
        }

        $bankQuestion = app(QuestionRepository::class)->findById($question->questionId());
        if ($bankQuestion === null) {
            throw QuestionNotFound::withId($question->questionId()->value());
        }

        return $bankQuestion;
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
