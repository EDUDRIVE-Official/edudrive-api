<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Legal\Application\Queries\GetOrganizationMinorsConsentsQuery;
use Modules\Legal\Application\Responses\OrganizationMinorConsentsResponse;

final class OrganizationConsentsController
{
    public function index(string $organizationId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetOrganizationMinorsConsentsQuery(organizationId: $organizationId));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (OrganizationMinorConsentsResponse $consent): array => $consent->toArray(),
            $result,
        )]);
    }
}
