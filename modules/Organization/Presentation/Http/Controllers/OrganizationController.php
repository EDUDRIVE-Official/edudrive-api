<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Organization\Application\Commands\AddCampusCommand;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\Responses\AddCampusResponse;
use Modules\Organization\Application\Responses\CreateOrganizationResponse;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;
use Modules\Organization\Presentation\Http\Requests\AddCampusRequest;
use Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;
use Symfony\Component\HttpFoundation\Response;

final class OrganizationController
{
    public function index(
        QueryBus $queryBus,
    ): JsonResponse {
        $result = $queryBus->ask(
            new ListOrganizationsQuery,
        );

        assert(is_array($result));

        /** @var list<OrganizationListItemResponse> $result */
        $data = array_map(
            static fn (OrganizationListItemResponse $organization): array => $organization->toArray(),
            $result,
        );

        return response()->json(['data' => $data]);
    }

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

    public function addCampus(
        string $organizationId,
        AddCampusRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new AddCampusCommand(
                organizationId: $organizationId,
                name: (string) $validated['name'],
            ),
        );

        assert($result instanceof AddCampusResponse);

        return response()->json(
            ['data' => $result->toArray()],
            Response::HTTP_CREATED,
        );
    }
}
