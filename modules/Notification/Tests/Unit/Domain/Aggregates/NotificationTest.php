<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationStatus;
use Modules\Notification\Domain\Exceptions\InvalidNotificationTransition;
use Modules\Notification\Domain\ValueObjects\NotificationId;

function newNotification(): Notification
{
    return Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        channel: NotificationChannel::Web,
        category: 'logro',
        subject: 'Nuevo logro obtenido',
        body: 'Has obtenido el logro Primer curso completado.',
        sentAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se envia como no leida', function (): void {
    $notification = newNotification();

    expect($notification->status())->toBe(NotificationStatus::Unread)
        ->and($notification->readAt())->toBeNull();
});

it('se marca como leida y registra la fecha', function (): void {
    $notification = newNotification();

    $notification->markAsRead(new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    expect($notification->status())->toBe(NotificationStatus::Read)
        ->and($notification->readAt())->not->toBeNull();
});

it('rechaza marcar como leida una notificacion ya leida', function (): void {
    $notification = newNotification();
    $notification->markAsRead(new DateTimeImmutable('now'));

    expect(fn () => $notification->markAsRead(new DateTimeImmutable('now')))
        ->toThrow(InvalidNotificationTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = NotificationId::fromString((string) Str::uuid());
    $userId = (string) Str::uuid();
    $sentAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $readAt = new DateTimeImmutable('2026-08-27T00:00:00+00:00');

    $notification = Notification::restore(
        id: $id,
        userId: $userId,
        channel: NotificationChannel::Email,
        category: 'certificado',
        subject: 'Certificado emitido',
        body: 'Se emitió tu certificado.',
        status: NotificationStatus::Read,
        sentAt: $sentAt,
        readAt: $readAt,
    );

    expect($notification->id()->equals($id))->toBeTrue()
        ->and($notification->userId())->toBe($userId)
        ->and($notification->channel())->toBe(NotificationChannel::Email)
        ->and($notification->category())->toBe('certificado')
        ->and($notification->subject())->toBe('Certificado emitido')
        ->and($notification->body())->toBe('Se emitió tu certificado.')
        ->and($notification->status())->toBe(NotificationStatus::Read)
        ->and($notification->sentAt())->toBe($sentAt)
        ->and($notification->readAt())->toBe($readAt);
});
