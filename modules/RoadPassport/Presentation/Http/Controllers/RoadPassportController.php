<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\RoadPassport\Application\Commands\ChangeRoadPassportLevelCommand;
use Modules\RoadPassport\Application\Commands\IssueRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\ReactivateRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\RevokeRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\SuspendRoadPassportCommand;
use Modules\RoadPassport\Application\Queries\GetMyRoadPassportQuery;
use Modules\RoadPassport\Application\Queries\GetRoadPassportQuery;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Presentation\Http\Requests\ChangeRoadPassportLevelRequest;
use Modules\RoadPassport\Presentation\Http\Requests\IssueRoadPassportRequest;
use Modules\RoadPassport\Presentation\Http\Requests\RevokeRoadPassportRequest;
use Modules\RoadPassport\Presentation\Http\Requests\SuspendRoadPassportRequest;
use Symfony\Component\HttpFoundation\Response;

final class RoadPassportController
{
    public function store(IssueRoadPassportRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new IssueRoadPassportCommand(userId: (string) $data['user_id']));
        assert($result instanceof RoadPassportResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyRoadPassportQuery(userId: (string) $user->getAuthIdentifier()));
        assert($result instanceof RoadPassportResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function show(
        string $roadPassportId,
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $permissionChecker,
    ): JsonResponse {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetRoadPassportQuery(
            roadPassportId: $roadPassportId,
            userId: (string) $user->getAuthIdentifier(),
            canViewOthers: $permissionChecker->userHasPermission((string) $user->getAuthIdentifier(), Permission::ViewRoadPassports),
        ));
        assert($result instanceof RoadPassportResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function suspend(string $roadPassportId, SuspendRoadPassportRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new SuspendRoadPassportCommand(
            roadPassportId: $roadPassportId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof RoadPassportResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function reactivate(string $roadPassportId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ReactivateRoadPassportCommand(roadPassportId: $roadPassportId));
        assert($result instanceof RoadPassportResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function revoke(string $roadPassportId, RevokeRoadPassportRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RevokeRoadPassportCommand(
            roadPassportId: $roadPassportId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof RoadPassportResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function changeLevel(string $roadPassportId, ChangeRoadPassportLevelRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new ChangeRoadPassportLevelCommand(
            roadPassportId: $roadPassportId,
            level: (int) $data['level'],
        ));
        assert($result instanceof RoadPassportResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
