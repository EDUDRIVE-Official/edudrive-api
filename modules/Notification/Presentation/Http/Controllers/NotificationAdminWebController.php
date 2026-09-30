<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Application\UseCases\ListUsersUseCase;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Presentation\Http\Requests\SendNotificationRequest;

final class NotificationAdminWebController
{
    private const array CATEGORY_LABELS = [
        'curso' => 'Cursos y aprendizaje',
        'logro' => 'Logros y reconocimientos',
        'certificado' => 'Certificados',
        'recordatorio' => 'Recordatorios',
        'seguridad' => 'Seguridad de la cuenta',
        'sistema' => 'Información del sistema',
        'acompañamiento' => 'Acompañamiento familiar',
    ];

    private const array CHANNEL_LABELS = [
        'web' => 'En EDUDRIVE',
        'email' => 'Correo electrónico',
        'mobile' => 'Notificación móvil',
        'internal_message' => 'Mensaje interno',
    ];

    public function create(ListUsersUseCase $listUsers): View
    {
        return view('notifications.admin', [
            'users' => $listUsers->execute(),
            'channels' => NotificationChannel::cases(),
            'categoryLabels' => self::CATEGORY_LABELS,
            'channelLabels' => self::CHANNEL_LABELS,
        ]);
    }

    public function store(SendNotificationRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new SendNotificationCommand(
            userId: (string) $data['user_id'],
            channel: (string) $data['channel'],
            category: (string) $data['category'],
            subject: (string) $data['subject'],
            body: (string) $data['body'],
            actionUrl: isset($data['action_url']) ? (string) $data['action_url'] : null,
        ));
        assert($result === null || $result instanceof NotificationResponse);

        if ($result === null) {
            return back()->withInput()->with('error', 'La preferencia del destinatario impidió el envío por ese canal o categoría.');
        }

        return back()->with('status', 'Notificación enviada correctamente.');
    }
}
