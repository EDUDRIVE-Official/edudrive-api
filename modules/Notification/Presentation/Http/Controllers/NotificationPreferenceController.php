<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Notification\Application\Commands\GiveNotificationConsentCommand;
use Modules\Notification\Application\Commands\RevokeNotificationConsentCommand;
use Modules\Notification\Application\Commands\UpdateNotificationPreferenceCommand;
use Modules\Notification\Application\Queries\GetMyNotificationPreferenceQuery;
use Modules\Notification\Application\Responses\NotificationPreferenceResponse;
use Modules\Notification\Presentation\Http\Requests\UpdateNotificationPreferenceRequest;

final class NotificationPreferenceController
{
    public function show(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyNotificationPreferenceQuery(userId: (string) $user->getAuthIdentifier()));
        assert($result instanceof NotificationPreferenceResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(UpdateNotificationPreferenceRequest $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $data = $request->validated();
        $result = $commandBus->dispatch(new UpdateNotificationPreferenceCommand(
            userId: (string) $user->getAuthIdentifier(),
            allowedChannels: $data['allowed_channels'],
            mutedCategories: $data['muted_categories'],
            frequency: (string) $data['frequency'],
            quietHoursStart: isset($data['quiet_hours_start']) ? (string) $data['quiet_hours_start'] : null,
            quietHoursEnd: isset($data['quiet_hours_end']) ? (string) $data['quiet_hours_end'] : null,
        ));
        assert($result instanceof NotificationPreferenceResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function giveConsent(Request $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new GiveNotificationConsentCommand(userId: (string) $user->getAuthIdentifier()));
        assert($result instanceof NotificationPreferenceResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function revokeConsent(Request $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new RevokeNotificationConsentCommand(userId: (string) $user->getAuthIdentifier()));
        assert($result instanceof NotificationPreferenceResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
