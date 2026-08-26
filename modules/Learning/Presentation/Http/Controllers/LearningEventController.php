<?php

declare(strict_types=1);

namespace Modules\Learning\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Responses\LearningEventResponse;

final class LearningEventController
{
    public function index(
        string $enrollmentId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetEnrollmentLearningEventsQuery(
            enrollmentId: $enrollmentId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewEnrollments),
        ));
        assert($result instanceof LearningEventResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
