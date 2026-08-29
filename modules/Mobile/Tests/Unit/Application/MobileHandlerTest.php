<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Mobile\Application\Commands\RegisterMobileDeviceCommand;
use Modules\Mobile\Application\Commands\RemoveMobileDeviceCommand;
use Modules\Mobile\Application\Exceptions\InvalidDevicePlatform;
use Modules\Mobile\Application\Exceptions\MobileDeviceNotFound;
use Modules\Mobile\Application\Queries\GetMobileSyncQuery;
use Modules\Mobile\Application\Queries\ListMobileDevicesQuery;
use Modules\Mobile\Application\Responses\MobileDeviceResponse;
use Modules\Mobile\Application\UseCases\GetMobileSyncHandler;
use Modules\Mobile\Application\UseCases\ListMobileDevicesHandler;
use Modules\Mobile\Application\UseCases\RegisterMobileDeviceHandler;
use Modules\Mobile\Application\UseCases\RemoveMobileDeviceHandler;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Enums\DevicePlatform;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Domain\ValueObjects\MobileDeviceId;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;

final class InMemoryMobileDeviceRepository implements MobileDeviceRepository
{
    /** @var array<string, MobileDevice> */
    public array $items = [];

    public function save(MobileDevice $device): void
    {
        $this->items[$device->id()->value()] = $device;
    }

    public function findById(MobileDeviceId $id): ?MobileDevice
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByUserAndDeviceId(string $userId, string $deviceId): ?MobileDevice
    {
        foreach ($this->items as $device) {
            if ($device->userId() === $userId && $device->deviceId() === $deviceId) {
                return $device;
            }
        }

        return null;
    }

    /** @return list<MobileDevice> */
    public function findByUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (MobileDevice $device): bool => $device->userId() === $userId,
        ));
    }

    /** @return list<MobileDevice> */
    public function findWithPushTokenByUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (MobileDevice $device): bool => $device->userId() === $userId && $device->hasPushToken(),
        ));
    }

    public function delete(MobileDeviceId $id): void
    {
        unset($this->items[$id->value()]);
    }
}

final class InMemoryMobileSyncEnrollmentRepository implements EnrollmentRepository
{
    /** @var array<string, Enrollment> */
    public array $items = [];

    public function save(Enrollment $enrollment): void
    {
        $this->items[$enrollment->id()->value()] = $enrollment;
    }

    public function findById(EnrollmentId $id): ?Enrollment
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findActiveOrPendingFor(CourseId $courseId, string $userId): ?Enrollment
    {
        return null;
    }

    /** @return list<Enrollment> */
    public function all(
        ?CourseId $courseId = null,
        ?string $userId = null,
        ?string $organizationId = null,
        ?EnrollmentStatus $status = null,
        ?EnrollmentSource $source = null,
    ): array {
        return array_values(array_filter(
            $this->items,
            static fn (Enrollment $enrollment): bool => $userId === null || $enrollment->userId() === $userId,
        ));
    }
}

final class InMemoryMobileSyncNotificationRepository implements NotificationRepository
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
            static fn (Notification $notification): bool => $notification->userId() === $userId,
        ));
    }
}

function persistedMobileDeviceFor(InMemoryMobileDeviceRepository $repository, string $userId): MobileDevice
{
    $device = MobileDevice::register(
        id: MobileDeviceId::fromString((string) Str::uuid()),
        userId: $userId,
        deviceId: 'device-'.strtoupper((string) Str::random(6)),
        platform: DevicePlatform::Ios,
        pushToken: 'push-token',
        appVersion: '1.0.0',
    );
    $repository->save($device);

    return $device;
}

it('registra un dispositivo nuevo', function (): void {
    $repository = new InMemoryMobileDeviceRepository;
    $userId = (string) Str::uuid();

    $response = (new RegisterMobileDeviceHandler($repository))->handle(new RegisterMobileDeviceCommand(
        userId: $userId,
        deviceId: 'device-1',
        platform: 'ios',
        pushToken: 'push-token-1',
        appVersion: '1.0.0',
    ));

    expect($response)->toBeInstanceOf(MobileDeviceResponse::class)
        ->and($response->deviceId)->toBe('device-1')
        ->and($response->platform)->toBe('ios')
        ->and($response->hasPushToken)->toBeTrue()
        ->and($repository->items)->toHaveCount(1);
});

it('actualiza el mismo dispositivo en vez de duplicarlo al re-registrarse', function (): void {
    $repository = new InMemoryMobileDeviceRepository;
    $userId = (string) Str::uuid();
    (new RegisterMobileDeviceHandler($repository))->handle(new RegisterMobileDeviceCommand($userId, 'device-1', 'ios', 'push-token-viejo', '1.0.0'));

    $response = (new RegisterMobileDeviceHandler($repository))->handle(new RegisterMobileDeviceCommand($userId, 'device-1', 'android', 'push-token-nuevo', '1.1.0'));

    expect($response->platform)->toBe('android')
        ->and($repository->items)->toHaveCount(1);
});

it('rechaza registrar un dispositivo con una plataforma invalida', function (): void {
    $repository = new InMemoryMobileDeviceRepository;

    expect(fn () => (new RegisterMobileDeviceHandler($repository))->handle(new RegisterMobileDeviceCommand(
        (string) Str::uuid(),
        'device-1',
        'windows-phone',
        null,
        '1.0.0',
    )))->toThrow(InvalidDevicePlatform::class);
});

it('elimina un dispositivo existente', function (): void {
    $repository = new InMemoryMobileDeviceRepository;
    $userId = (string) Str::uuid();
    $device = persistedMobileDeviceFor($repository, $userId);

    (new RemoveMobileDeviceHandler($repository))->handle(new RemoveMobileDeviceCommand($userId, $device->deviceId()));

    expect($repository->items)->toBeEmpty();
});

it('rechaza eliminar un dispositivo inexistente o de otro usuario', function (): void {
    $repository = new InMemoryMobileDeviceRepository;
    $device = persistedMobileDeviceFor($repository, (string) Str::uuid());

    expect(fn () => (new RemoveMobileDeviceHandler($repository))->handle(new RemoveMobileDeviceCommand((string) Str::uuid(), $device->deviceId())))
        ->toThrow(MobileDeviceNotFound::class);
});

it('lista los dispositivos de un usuario', function (): void {
    $repository = new InMemoryMobileDeviceRepository;
    $userId = (string) Str::uuid();
    persistedMobileDeviceFor($repository, $userId);
    persistedMobileDeviceFor($repository, $userId);
    persistedMobileDeviceFor($repository, (string) Str::uuid());

    $responses = (new ListMobileDevicesHandler($repository))->handle(new ListMobileDevicesQuery($userId));

    expect($responses)->toHaveCount(2);
});

it('sincroniza inscripciones y notificaciones creadas despues de la fecha dada', function (): void {
    $enrollments = new InMemoryMobileSyncEnrollmentRepository;
    $notifications = new InMemoryMobileSyncNotificationRepository;
    $userId = (string) Str::uuid();

    $oldEnrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: $userId,
        enrolledAt: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
    );
    $newEnrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: $userId,
        enrolledAt: new DateTimeImmutable('2026-08-29T00:00:00+00:00'),
    );
    $enrollments->save($oldEnrollment);
    $enrollments->save($newEnrollment);

    $oldNotification = Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $userId,
        channel: NotificationChannel::Mobile,
        category: 'general',
        subject: 'Vieja',
        body: 'Cuerpo',
        sentAt: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
    );
    $newNotification = Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $userId,
        channel: NotificationChannel::Mobile,
        category: 'general',
        subject: 'Nueva',
        body: 'Cuerpo',
        sentAt: new DateTimeImmutable('2026-08-29T00:00:00+00:00'),
    );
    $notifications->save($oldNotification);
    $notifications->save($newNotification);

    $response = (new GetMobileSyncHandler($enrollments, $notifications))->handle(new GetMobileSyncQuery($userId, '2026-08-15T00:00:00+00:00'));

    expect($response->enrollments)->toHaveCount(1)
        ->and($response->enrollments[0]->id)->toBe($newEnrollment->id()->value())
        ->and($response->notifications)->toHaveCount(1)
        ->and($response->notifications[0]->id)->toBe($newNotification->id()->value())
        ->and($response->syncedAt)->not->toBeNull();
});

it('devuelve todo cuando no se especifica una fecha de referencia', function (): void {
    $enrollments = new InMemoryMobileSyncEnrollmentRepository;
    $notifications = new InMemoryMobileSyncNotificationRepository;
    $userId = (string) Str::uuid();

    $enrollments->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: $userId,
        enrolledAt: new DateTimeImmutable('2020-01-01T00:00:00+00:00'),
    ));

    $response = (new GetMobileSyncHandler($enrollments, $notifications))->handle(new GetMobileSyncQuery($userId, null));

    expect($response->enrollments)->toHaveCount(1);
});
