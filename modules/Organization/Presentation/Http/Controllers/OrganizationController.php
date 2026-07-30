<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Responses\CreateOrganizationResponse;
use Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;
use Symfony\Component\HttpFoundation\Response;

final class OrganizationController
{
    public function store(
        CreateOrganizationRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new CreateOrganizationCommand(
                name: (string) $validated['name'],
                type: (string) $validated['type'],
            ),
        );

        assert($result instanceof CreateOrganizationResponse);

        return response()->json(
            ['data' => $result->toArray()],
            Response::HTTP_CREATED,
        );
    }
}
