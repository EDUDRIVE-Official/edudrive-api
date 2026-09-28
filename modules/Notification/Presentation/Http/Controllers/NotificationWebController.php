<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Notification\Application\Commands\GiveNotificationConsentCommand;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Commands\RevokeNotificationConsentCommand;
use Modules\Notification\Application\Commands\UpdateNotificationPreferenceCommand;
use Modules\Notification\Application\Queries\GetMyNotificationPreferenceQuery;
use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\Responses\NotificationPreferenceResponse;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;
use Modules\Notification\Presentation\Http\Requests\UpdateNotificationPreferenceRequest;

final class NotificationWebController
{
    public function index(Request $request, QueryBus $queryBus): View
    {
        $userId = (string) $request->user()?->getAuthIdentifier();
        $notifications = $queryBus->ask(new GetMyNotificationsQuery(userId: $userId));
        $preference = $queryBus->ask(new GetMyNotificationPreferenceQuery(userId: $userId));
        assert(is_array($notifications));
        assert($preference instanceof NotificationPreferenceResponse);

        return view('notifications.index', [
            'notifications' => array_reverse(array_map(
                static fn (NotificationResponse $notification): array => $notification->toArray(),
                $notifications,
            )),
            'preference' => $preference->toArray(),
            'channels' => NotificationChannel::cases(),
            'frequencies' => NotificationFrequency::cases(),
        ]);
    }

    public function markAsRead(string $notificationId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        try {
            $commandBus->dispatch(new MarkNotificationAsReadCommand(
                notificationId: $notificationId,
                userId: (string) $request->user()?->getAuthIdentifier(),
            ));
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Notificación marcada como leída.');
    }

    public function openAction(
        string $notificationId,
        Request $request,
        CommandBus $commandBus,
        NotificationRepository $notifications,
    ): RedirectResponse {
        $userId = (string) $request->user()?->getAuthIdentifier();
        $notification = $notifications->findById(NotificationId::fromString($notificationId));
        if ($notification === null || $notification->userId() !== $userId || $notification->actionUrl() === null) {
            abort(404);
        }

        if ($notification->status()->value === 'unread') {
            $commandBus->dispatch(new MarkNotificationAsReadCommand(
                notificationId: $notificationId,
                userId: $userId,
            ));
        }

        return redirect()->to($notification->actionUrl());
    }

    public function updatePreferences(UpdateNotificationPreferenceRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();
        $commandBus->dispatch(new UpdateNotificationPreferenceCommand(
            userId: (string) $request->user()?->getAuthIdentifier(),
            allowedChannels: $data['allowed_channels'],
            mutedCategories: $data['muted_categories'],
            frequency: (string) $data['frequency'],
            quietHoursStart: isset($data['quiet_hours_start']) ? (string) $data['quiet_hours_start'] : null,
            quietHoursEnd: isset($data['quiet_hours_end']) ? (string) $data['quiet_hours_end'] : null,
        ));

        return back()->with('status', 'Preferencias actualizadas.');
    }

    public function giveConsent(Request $request, CommandBus $commandBus): RedirectResponse
    {
        $commandBus->dispatch(new GiveNotificationConsentCommand(
            userId: (string) $request->user()?->getAuthIdentifier(),
        ));

        return back()->with('status', 'Consentimiento de notificaciones activado.');
    }

    public function revokeConsent(Request $request, CommandBus $commandBus): RedirectResponse
    {
        $commandBus->dispatch(new RevokeNotificationConsentCommand(
            userId: (string) $request->user()?->getAuthIdentifier(),
        ));

        return back()->with('status', 'Consentimiento de notificaciones desactivado.');
    }
}
