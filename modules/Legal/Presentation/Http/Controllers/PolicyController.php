<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Legal\Application\Commands\PublishPolicyVersionCommand;
use Modules\Legal\Application\Queries\ListPoliciesQuery;
use Modules\Legal\Application\Responses\PolicyResponse;
use Modules\Legal\Presentation\Http\Requests\PublishPolicyVersionRequest;
use Symfony\Component\HttpFoundation\Response;

final class PolicyController
{
    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListPoliciesQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (PolicyResponse $policy): array => $policy->toArray(),
            $result,
        )]);
    }

    public function store(
        PublishPolicyVersionRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $data = $request->validated();

        $result = $commandBus->dispatch(new PublishPolicyVersionCommand(
            key: (string) $data['key'],
            effectiveAt: isset($data['effective_at']) ? (string) $data['effective_at'] : null,
        ));

        assert($result instanceof PolicyResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }
}
