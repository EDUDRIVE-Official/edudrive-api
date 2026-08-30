<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Modules\Legal\Application\Commands\RevokeConsentCommand;
use Modules\Legal\Application\Queries\GetMyConsentsQuery;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Presentation\Http\Requests\RecordConsentRequest;
use Symfony\Component\HttpFoundation\Response;

final class ConsentController
{
    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        $result = $queryBus->ask(new GetMyConsentsQuery(userId: $userId));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (ConsentResponse $consent): array => $consent->toArray(),
            $result,
        )]);
    }

    public function store(RecordConsentRequest $request, CommandBus $commandBus): JsonResponse
    {
        $userId = (string) $request->user()?->getAuthIdentifier();
        $data = $request->validated();

        $result = $commandBus->dispatch(new RecordConsentCommand(
            userId: $userId,
            policyKey: (string) $data['policy_key'],
            guardianDeclaration: isset($data['guardian_declaration']) ? (string) $data['guardian_declaration'] : null,
        ));

        assert($result instanceof ConsentResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function destroy(string $policyKey, Request $request, CommandBus $commandBus): JsonResponse
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        $result = $commandBus->dispatch(new RevokeConsentCommand(
            userId: $userId,
            policyKey: $policyKey,
        ));
        assert($result instanceof ConsentResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
