<?php

declare(strict_types=1);

namespace Modules\Learning\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Responses\LearningEventResponse;

final class LearningEventWebController
{
    public function __invoke(string $enrollmentId, Request $request, QueryBus $queryBus, PermissionChecker $permissionChecker): View
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        try {
            $result = $queryBus->ask(new GetEnrollmentLearningEventsQuery(
                enrollmentId: $enrollmentId,
                userId: $userId,
                canViewOthers: $permissionChecker->userHasPermission($userId, Permission::ViewEnrollments),
            ));
        } catch (EnrollmentNotFound) {
            abort(404);
        }
        assert($result instanceof LearningEventResponse);

        return view('learning.activity', ['activity' => $result->toArray()]);
    }
}
