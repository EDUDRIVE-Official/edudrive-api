<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Authorization\Presentation\Http\Requests\AssignRoleRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final class RoleAssignmentController
{
    public function store(
        AssignRoleRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new AssignRoleCommand(
                userId: (string) $validated['user_id'],
                role: (string) $validated['role'],
                organizationId: isset($validated['organization_id'])
                    ? (string) $validated['organization_id']
                    : null,
            ),
        );

        assert($result instanceof RoleAssignmentResponse);

        return response()->json(
            ['data' => $result->toArray()],
            Response::HTTP_CREATED,
        );
    }
}
