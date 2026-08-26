<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Gamification\Application\Commands\CreateAchievementCommand;
use Modules\Gamification\Application\Commands\GrantAchievementCommand;
use Modules\Gamification\Application\Commands\RetireAchievementCommand;
use Modules\Gamification\Application\Queries\GetAchievementQuery;
use Modules\Gamification\Application\Queries\GetMyAchievementsQuery;
use Modules\Gamification\Application\Queries\ListAchievementsQuery;
use Modules\Gamification\Application\Responses\AchievementResponse;
use Modules\Gamification\Application\Responses\UserAchievementResponse;
use Modules\Gamification\Presentation\Http\Requests\CreateAchievementRequest;
use Modules\Gamification\Presentation\Http\Requests\GrantAchievementRequest;
use Modules\Gamification\Presentation\Http\Requests\RetireAchievementRequest;
use Symfony\Component\HttpFoundation\Response;

final class AchievementController
{
    public function store(CreateAchievementRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateAchievementCommand(
            code: (string) $data['code'],
            name: (string) $data['name'],
            description: (string) $data['description'],
            earningRule: (string) $data['earning_rule'],
        ));
        assert($result instanceof AchievementResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListAchievementsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AchievementResponse $achievement): array => $achievement->toArray(),
            $result,
        )]);
    }

    public function show(string $achievementId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAchievementQuery(achievementId: $achievementId));
        assert($result instanceof AchievementResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function retire(string $achievementId, RetireAchievementRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RetireAchievementCommand(
            achievementId: $achievementId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof AchievementResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function grant(string $achievementId, GrantAchievementRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new GrantAchievementCommand(
            achievementId: $achievementId,
            userId: (string) $data['user_id'],
            evidence: (string) $data['evidence'],
        ));
        assert($result instanceof UserAchievementResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyAchievementsQuery(userId: (string) $user->getAuthIdentifier()));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (UserAchievementResponse $userAchievement): array => $userAchievement->toArray(),
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
