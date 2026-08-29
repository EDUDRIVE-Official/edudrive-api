<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;
use Modules\Notification\Infrastructure\Mail\NotificationMail;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedNotificationFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de notificaciones feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedNotificationFeature(string $userId): Notification
{
    $notification = Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $userId,
        channel: NotificationChannel::Web,
        category: 'logro',
        subject: 'Nuevo logro obtenido',
        body: 'Has obtenido el logro Primer curso completado.',
    );
    app(NotificationRepository::class)->save($notification);

    return $notification;
}

it('envia una notificacion con el permiso notifications.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $userId = persistedNotificationFeatureUserId();

    $this->postJson('/api/v1/notification/notifications', [
        'user_id' => $userId,
        'channel' => 'web',
        'category' => 'logro',
        'subject' => 'Nuevo logro obtenido',
        'body' => 'Has obtenido el logro Primer curso completado.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $userId)
        ->assertJsonPath('data.status', 'unread');
});

it('envia un correo real cuando el canal es email', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $userId = persistedNotificationFeatureUserId();
    $user = app(UserRepository::class)->findById($userId);
    Mail::fake();

    $this->postJson('/api/v1/notification/notifications', [
        'user_id' => $userId,
        'channel' => 'email',
        'category' => 'certificado',
        'subject' => 'Tu certificado esta listo',
        'body' => 'Descarga tu certificado desde el panel.',
    ])->assertCreated();

    Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($user): bool {
        return $mail->hasTo($user?->email()->value())
            && $mail->notificationSubject === 'Tu certificado esta listo';
    });
});

it('rechaza enviar una notificacion sin el permiso notifications.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $userId = persistedNotificationFeatureUserId();

    $this->postJson('/api/v1/notification/notifications', [
        'user_id' => $userId,
        'channel' => 'web',
        'category' => 'logro',
        'subject' => 'Asunto',
        'body' => 'Cuerpo',
    ])->assertForbidden();
});

it('el usuario consulta sus propias notificaciones', function (): void {
    /** @var TestCase $this */
    $userId = persistedNotificationFeatureUserId();
    $notification = persistedNotificationFeature($userId);
    actingAsUserId($userId);

    $this->getJson('/api/v1/notification/notifications/me')
        ->assertOk()
        ->assertJsonPath('data.0.id', $notification->id()->value());
});

it('el usuario marca su propia notificacion como leida', function (): void {
    /** @var TestCase $this */
    $userId = persistedNotificationFeatureUserId();
    $notification = persistedNotificationFeature($userId);
    actingAsUserId($userId);

    $this->postJson("/api/v1/notification/notifications/{$notification->id()->value()}/read")
        ->assertOk()
        ->assertJsonPath('data.status', 'read');
});

it('rechaza marcar como leida una notificacion de otro usuario', function (): void {
    /** @var TestCase $this */
    $notification = persistedNotificationFeature(persistedNotificationFeatureUserId());
    actingAsUserId((string) Str::uuid());

    $this->postJson("/api/v1/notification/notifications/{$notification->id()->value()}/read")
        ->assertStatus(404)
        ->assertJsonPath('code', 'NOTIFICATION_NOT_FOUND');
});

it('rechaza marcar como leida una notificacion inexistente', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());

    $this->postJson('/api/v1/notification/notifications/'.((string) Str::uuid()).'/read')
        ->assertStatus(404)
        ->assertJsonPath('code', 'NOTIFICATION_NOT_FOUND');
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $notification = persistedNotificationFeature(persistedNotificationFeatureUserId());

    $this->getJson('/api/v1/notification/notifications/me')->assertUnauthorized();
    $this->postJson("/api/v1/notification/notifications/{$notification->id()->value()}/read")->assertUnauthorized();
    $this->postJson('/api/v1/notification/notifications', [])->assertUnauthorized();
});
