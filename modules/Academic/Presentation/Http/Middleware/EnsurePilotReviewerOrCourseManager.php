<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsurePilotReviewerOrCourseManager
{
    public function __construct(private PermissionChecker $permissions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_if($user === null, 401);
        $userId = (string) $user->getAuthIdentifier();
        $canManage = $this->permissions->userHasPermission($userId, Permission::ManageCourses);
        $isDesignated = DB::table('academic_pilot_visual_reviewers')
            ->where('user_id', $userId)
            ->where('active', true)
            ->exists();

        abort_unless($canManage || $isDesignated, 403, 'No tiene una designación activa para revisar el piloto P912.');

        return $next($request);
    }
}
