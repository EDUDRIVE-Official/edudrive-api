<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Responses\CurriculumUnlockResponse;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Presentation\Http\Requests\CompleteLessonRequest;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;

final class EnrollmentProgressController
{
    public function complete(
        string $enrollmentId,
        string $lessonId,
        CompleteLessonRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new CompleteLessonCommand(
            enrollmentId: $enrollmentId,
            lessonId: $lessonId,
            userId: (string) $user->getAuthIdentifier(),
            timeSpentMinutes: $request->validated('time_spent_minutes') === null
                ? null
                : (int) $request->validated('time_spent_minutes'),
        ));
        assert($result instanceof EnrollmentProgressResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function show(
        string $enrollmentId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetEnrollmentProgressQuery(
            enrollmentId: $enrollmentId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewEnrollments),
        ));
        assert($result instanceof EnrollmentProgressResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function curriculum(
        string $enrollmentId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetEnrollmentCurriculumStatusQuery(
            enrollmentId: $enrollmentId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewEnrollments),
        ));
        assert($result instanceof CurriculumUnlockResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
