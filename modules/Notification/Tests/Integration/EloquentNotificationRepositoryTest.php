<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationStatus;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;

uses(RefreshDatabase::class);

function persistedNotificationUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de notificaciones',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function newPersistableNotification(string $userId): Notification
{
    return Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $userId,
        channel: NotificationChannel::Web,
        category: 'logro',
        subject: 'Nuevo logro obtenido',
        body: 'Has obtenido el logro Primer curso completado.',
        sentAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('guarda y recupera una notificacion por identificador', function (): void {
    $userId = persistedNotificationUserId();
    $notification = newPersistableNotification($userId);

    app(NotificationRepository::class)->save($notification);
    $found = app(NotificationRepository::class)->findById($notification->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($notification->id()))->toBeTrue()
        ->and($found?->channel())->toBe(NotificationChannel::Web)
        ->and($found?->category())->toBe('logro')
        ->and($found?->status())->toBe(NotificationStatus::Unread)
        ->and($found?->readAt())->toBeNull();
});

it('guarda y recupera una notificacion leida con su fecha', function (): void {
    $userId = persistedNotificationUserId();
    $notification = newPersistableNotification($userId);
    $notification->markAsRead(new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    app(NotificationRepository::class)->save($notification);
    $found = app(NotificationRepository::class)->findById($notification->id());

    expect($found?->status())->toBe(NotificationStatus::Read)
        ->and($found?->readAt())->not->toBeNull();
});

it('lista solo las notificaciones del usuario solicitado', function (): void {
    $userId = persistedNotificationUserId();
    $otherUserId = persistedNotificationUserId();
    $repository = app(NotificationRepository::class);

    $repository->save(newPersistableNotification($userId));
    $repository->save(newPersistableNotification($userId));
    $repository->save(newPersistableNotification($otherUserId));

    expect($repository->allForUser($userId))->toHaveCount(2);
});

it('no encuentra una notificacion inexistente', function (): void {
    expect(app(NotificationRepository::class)->findById(NotificationId::fromString((string) Str::uuid())))->toBeNull();
});
