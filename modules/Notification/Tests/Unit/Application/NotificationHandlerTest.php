<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Mobile\Application\Services\MobilePushSender;
use Modules\Notification\Application\Commands\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Commands\SendNotificationCommand;
use Modules\Notification\Application\Exceptions\NotificationNotFound;
use Modules\Notification\Application\Queries\GetMyNotificationsQuery;
use Modules\Notification\Application\Responses\NotificationResponse;
use Modules\Notification\Application\UseCases\GetMyNotificationsHandler;
use Modules\Notification\Application\UseCases\MarkNotificationAsReadHandler;
use Modules\Notification\Application\UseCases\SendNotificationHandler;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Aggregates\NotificationPreference;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;

final class InMemoryNotificationRepository implements NotificationRepository
{
    /** @var array<string, Notification> */
    public array $items = [];

    public function save(Notification $notification): void
    {
        $this->items[$notification->id()->value()] = $notification;
    }

    public function findById(NotificationId $id): ?Notification
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<Notification> */
    public function allForUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (Notification $item): bool => $item->userId() === $userId,
        ));
    }
}

final class InMemoryNotificationPreferenceRepository implements NotificationPreferenceRepository
{
    /** @var array<string, NotificationPreference> */
    public array $items = [];

    public function save(NotificationPreference $preference): void
    {
        $this->items[$preference->userId()] = $preference;
    }

    public function findByUserId(string $userId): ?NotificationPreference
    {
        return $this->items[$userId] ?? null;
    }
}

it('envia una notificacion nueva a un usuario', function (): void {
    $notifications = new InMemoryNotificationRepository;
    $preferences = new InMemoryNotificationPreferenceRepository;
    $userId = (string) Str::uuid();

    $response = (new SendNotificationHandler($notifications, $preferences, app(MobilePushSender::class)))->handle(new SendNotificationCommand(
        userId: $userId,
        channel: 'web',
        category: 'logro',
        subject: 'Nuevo logro obtenido',
        body: 'Has obtenido el logro Primer curso completado.',
    ));

    expect($response)->toBeInstanceOf(NotificationResponse::class)
        ->and($response->userId)->toBe($userId)
        ->and($response->channel)->toBe('web')
        ->and($response->status)->toBe('unread');
});

it('descarta el envio cuando la preferencia no permite el canal', function (): void {
    $notifications = new InMemoryNotificationRepository;
    $preferences = new InMemoryNotificationPreferenceRepository;
    $userId = (string) Str::uuid();
    $preference = NotificationPreference::default($userId);
    $preference->update(
        allowedChannels: [NotificationChannel::Email],
        mutedCategories: [],
        frequency: NotificationFrequency::Immediate,
        quietHoursStart: null,
        quietHoursEnd: null,
    );
    $preferences->save($preference);

    $response = (new SendNotificationHandler($notifications, $preferences, app(MobilePushSender::class)))->handle(new SendNotificationCommand($userId, 'web', 'logro', 'Asunto', 'Cuerpo'));

    expect($response)->toBeNull()
        ->and($notifications->allForUser($userId))->toBe([]);
});

it('marca como leida una notificacion propia', function (): void {
    $notifications = new InMemoryNotificationRepository;
    $preferences = new InMemoryNotificationPreferenceRepository;
    $userId = (string) Str::uuid();
    $sent = (new SendNotificationHandler($notifications, $preferences, app(MobilePushSender::class)))->handle(new SendNotificationCommand($userId, 'web', 'logro', 'Asunto', 'Cuerpo'));
    assert($sent instanceof NotificationResponse);

    $response = (new MarkNotificationAsReadHandler($notifications))->handle(new MarkNotificationAsReadCommand($sent->id, $userId));

    expect($response->status)->toBe('read');
});

it('rechaza marcar como leida una notificacion inexistente', function (): void {
    $notifications = new InMemoryNotificationRepository;

    expect(fn () => (new MarkNotificationAsReadHandler($notifications))->handle(new MarkNotificationAsReadCommand((string) Str::uuid(), (string) Str::uuid())))
        ->toThrow(NotificationNotFound::class);
});

it('rechaza marcar como leida una notificacion de otro usuario', function (): void {
    $notifications = new InMemoryNotificationRepository;
    $preferences = new InMemoryNotificationPreferenceRepository;
    $sent = (new SendNotificationHandler($notifications, $preferences, app(MobilePushSender::class)))->handle(new SendNotificationCommand((string) Str::uuid(), 'web', 'logro', 'Asunto', 'Cuerpo'));
    assert($sent instanceof NotificationResponse);

    expect(fn () => (new MarkNotificationAsReadHandler($notifications))->handle(new MarkNotificationAsReadCommand($sent->id, (string) Str::uuid())))
        ->toThrow(NotificationNotFound::class);
});

it('lista las notificaciones del usuario autenticado', function (): void {
    $notifications = new InMemoryNotificationRepository;
    $preferences = new InMemoryNotificationPreferenceRepository;
    $userId = (string) Str::uuid();
    (new SendNotificationHandler($notifications, $preferences, app(MobilePushSender::class)))->handle(new SendNotificationCommand($userId, 'web', 'logro', 'Asunto 1', 'Cuerpo 1'));
    (new SendNotificationHandler($notifications, $preferences, app(MobilePushSender::class)))->handle(new SendNotificationCommand($userId, 'email', 'certificado', 'Asunto 2', 'Cuerpo 2'));
    (new SendNotificationHandler($notifications, $preferences, app(MobilePushSender::class)))->handle(new SendNotificationCommand((string) Str::uuid(), 'web', 'logro', 'De otro usuario', 'Cuerpo'));

    $responses = (new GetMyNotificationsHandler($notifications))->handle(new GetMyNotificationsQuery($userId));

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(NotificationResponse::class);
});
