<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Presentation\Http\Requests\SendNotificationRequest;
use Symfony\Component\HttpFoundation\Response;

final class NotificationController
{
    public function store(SendNotificationRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new SendNotificationCommand(
            userId: (string) $data['user_id'],
            channel: (string) $data['channel'],
            category: (string) $data['category'],
            subject: (string) $data['subject'],
            body: (string) $data['body'],
        ));
        assert($result instanceof NotificationResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function me(Request $request, QueryBus $queryBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $queryBus->ask(new GetMyNotificationsQuery(userId: (string) $user->getAuthIdentifier()));
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (NotificationResponse $notification): array => $notification->toArray(),
            $result,
        )]);
    }

    public function markAsRead(string $notificationId, Request $request, CommandBus $commandBus): JsonResponse
    {
        $user = self::authenticatedUser($request);
        $result = $commandBus->dispatch(new MarkNotificationAsReadCommand(
            notificationId: $notificationId,
            userId: (string) $user->getAuthIdentifier(),
        ));
        assert($result instanceof NotificationResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    private static function authenticatedUser(Request $request): Authenticatable
    {
        $user = $request->user();
        assert($user instanceof Authenticatable);

        return $user;
    }
}
