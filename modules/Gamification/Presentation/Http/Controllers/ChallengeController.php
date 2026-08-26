<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Gamification\Application\Commands\CompleteChallengeParticipationCommand;
use Modules\Gamification\Application\Commands\CreateChallengeCommand;
use Modules\Gamification\Application\Commands\JoinChallengeCommand;
use Modules\Gamification\Application\Commands\RetireChallengeCommand;
use Modules\Gamification\Application\Queries\GetChallengeQuery;
use Modules\Gamification\Application\Queries\GetMyChallengeParticipationsQuery;
use Modules\Gamification\Application\Queries\ListChallengesQuery;
use Modules\Gamification\Application\Responses\ChallengeParticipationResponse;
use Modules\Gamification\Application\Responses\ChallengeResponse;
use Modules\Gamification\Presentation\Http\Requests\CompleteChallengeParticipationRequest;
use Modules\Gamification\Presentation\Http\Requests\CreateChallengeRequest;
use Modules\Gamification\Presentation\Http\Requests\JoinChallengeRequest;
use Modules\Gamification\Presentation\Http\Requests\RetireChallengeRequest;
use Symfony\Component\HttpFoundation\Response;

final class ChallengeController
{
    public function store(CreateChallengeRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateChallengeCommand(
            code: (string) $data['code'],
            name: (string) $data['name'],
            description: (string) $data['description'],
            type: (string) $data['type'],
            reward: (string) $data['reward'],
            startsAt: new DateTimeImmutable((string) $data['starts_at']),
            endsAt: new DateTimeImmutable((string) $data['ends_at']),
        ));
        assert($result instanceof ChallengeResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListChallengesQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (ChallengeResponse $challenge): array => $challenge->toArray(),
            $result,
        )]);
    }

    public function show(string $challengeId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetChallengeQuery(challengeId: $challengeId));
        assert($result instanceof ChallengeResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function retire(string $challengeId, RetireChallengeRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RetireChallengeCommand(
            challengeId: $challengeId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof ChallengeResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function join(string $challengeId, JoinChallengeRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new JoinChallengeCommand(
            challengeId: $challengeId,
            userId: (string) $data['user_id'],
        ));
        assert($result instanceof ChallengeParticipationResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function complete(string $challengeId, CompleteChallengeParticipationRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CompleteChallengeParticipationCommand(
            challengeId: $challengeId,
            userId: (string) $data['user_id'],
            evidence: isset($data['evidence']) ? (string) $data['evidence'] : null,
        ));
        assert($result instanceof ChallengeParticipationResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyChallengeParticipationsQuery(userId: (string) $user->getAuthIdentifier()));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (ChallengeParticipationResponse $participation): array => $participation->toArray(),
            $result,
        )]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
