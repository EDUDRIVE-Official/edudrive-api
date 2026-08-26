<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\StartTheoryExamSimulationCommand;
use Modules\Academic\Application\Queries\GetTheoryExamQuery;
use Modules\Academic\Application\Queries\ListTheoryExamAttemptsQuery;
use Modules\Academic\Application\Queries\ListTheoryExamsQuery;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Application\Responses\TheoryExamAttemptListItemResponse;
use Modules\Academic\Application\Responses\TheoryExamListItemResponse;
use Modules\Academic\Presentation\Http\Requests\StartTheoryExamSimulationRequest;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class TheoryExamController
{
    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListTheoryExamsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (TheoryExamListItemResponse $exam): array => $exam->toArray(),
            $result,
        )]);
    }

    public function show(string $examId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetTheoryExamQuery($examId));
        assert($result instanceof ExamResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function start(string $examId, StartTheoryExamSimulationRequest $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new StartTheoryExamSimulationCommand($examId, (string) $user->getAuthIdentifier()));
        assert($result instanceof ExamAttemptResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function attempts(Request $request, QueryBus $queryBus, PermissionChecker $permissionChecker): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $requestedUserId = $request->query('user_id');
        $canViewOthers = $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewExamAttempts);

        $result = $queryBus->ask(new ListTheoryExamAttemptsQuery(
            userId: (string) $user->getAuthIdentifier(),
            targetUserId: $canViewOthers && is_string($requestedUserId) && $requestedUserId !== '' ? $requestedUserId : null,
            licenseCategory: is_string($request->query('license_category')) && $request->query('license_category') !== '' ? (string) $request->query('license_category') : null,
        ));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (TheoryExamAttemptListItemResponse $attempt): array => $attempt->toArray(),
            $result,
        )]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
