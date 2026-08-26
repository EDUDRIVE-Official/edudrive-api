<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Gamification\Application\Commands\RecordExperienceCommand;
use Modules\Gamification\Application\Queries\GetMyExperienceSummaryQuery;
use Modules\Gamification\Application\Responses\ExperienceEntryResponse;
use Modules\Gamification\Application\Responses\ExperienceSummaryResponse;
use Modules\Gamification\Presentation\Http\Requests\RecordExperienceRequest;
use Symfony\Component\HttpFoundation\Response;

final class ExperienceController
{
    public function grant(RecordExperienceRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RecordExperienceCommand(
            userId: (string) $data['user_id'],
            points: (int) $data['points'],
            competencyId: isset($data['competency_id']) ? (string) $data['competency_id'] : null,
            reason: (string) $data['reason'],
        ));
        assert($result instanceof ExperienceEntryResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyExperienceSummaryQuery(userId: (string) $user->getAuthIdentifier()));
        assert($result instanceof ExperienceSummaryResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
