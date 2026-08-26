<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Gamification\Application\Commands\CreateBadgeCommand;
use Modules\Gamification\Application\Commands\GrantBadgeCommand;
use Modules\Gamification\Application\Commands\RetireBadgeCommand;
use Modules\Gamification\Application\Commands\UpdateBadgeCommand;
use Modules\Gamification\Application\Queries\GetBadgeQuery;
use Modules\Gamification\Application\Queries\GetMyBadgesQuery;
use Modules\Gamification\Application\Queries\ListBadgesQuery;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Application\Responses\UserBadgeResponse;
use Modules\Gamification\Presentation\Http\Requests\CreateBadgeRequest;
use Modules\Gamification\Presentation\Http\Requests\GrantBadgeRequest;
use Modules\Gamification\Presentation\Http\Requests\RetireBadgeRequest;
use Modules\Gamification\Presentation\Http\Requests\UpdateBadgeRequest;
use Symfony\Component\HttpFoundation\Response;

final class BadgeController
{
    public function store(CreateBadgeRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateBadgeCommand(
            code: (string) $data['code'],
            name: (string) $data['name'],
            description: (string) $data['description'],
            criteria: (string) $data['criteria'],
            category: (string) $data['category'],
            level: (string) $data['level'],
        ));
        assert($result instanceof BadgeResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListBadgesQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (BadgeResponse $badge): array => $badge->toArray(),
            $result,
        )]);
    }

    public function show(string $badgeId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetBadgeQuery(badgeId: $badgeId));
        assert($result instanceof BadgeResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(string $badgeId, UpdateBadgeRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new UpdateBadgeCommand(
            badgeId: $badgeId,
            name: (string) $data['name'],
            description: (string) $data['description'],
            criteria: (string) $data['criteria'],
            category: (string) $data['category'],
            level: (string) $data['level'],
        ));
        assert($result instanceof BadgeResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function retire(string $badgeId, RetireBadgeRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RetireBadgeCommand(
            badgeId: $badgeId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof BadgeResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function grant(string $badgeId, GrantBadgeRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new GrantBadgeCommand(
            badgeId: $badgeId,
            userId: (string) $data['user_id'],
            evidence: (string) $data['evidence'],
        ));
        assert($result instanceof UserBadgeResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyBadgesQuery(userId: (string) $user->getAuthIdentifier()));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (UserBadgeResponse $userBadge): array => $userBadge->toArray(),
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
