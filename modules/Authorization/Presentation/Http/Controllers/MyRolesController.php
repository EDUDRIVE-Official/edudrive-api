<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Queries\ListMyRolesQuery;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Foundation\Application\Bus\QueryBus;

final class MyRolesController
{
    public function __invoke(
        Request $request,
        QueryBus $queryBus,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $result = $queryBus->ask(
            new ListMyRolesQuery(
                userId: (string) $user->getAuthIdentifier(),
            ),
        );

        assert(is_array($result));

        /** @var list<RoleAssignmentResponse> $result */
        $data = array_map(
            static fn (RoleAssignmentResponse $assignment): array => $assignment->toArray(),
            $result,
        );

        return response()->json(['data' => $data]);
    }
}
