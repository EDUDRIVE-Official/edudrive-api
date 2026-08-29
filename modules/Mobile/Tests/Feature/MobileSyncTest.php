<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('sincroniza inscripciones y notificaciones propias creadas despues de la fecha dada', function (): void {
    /** @var TestCase $this */
    $user = actingAsRole(Role::Student);
    $userId = (string) $user->id;
    $course = createDraftCourseForPublishing('MOB-'.strtoupper((string) Str::random(4)));

    app(EnrollmentRepository::class)->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId,
        enrolledAt: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
    ));
    $newEnrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId,
        enrolledAt: new DateTimeImmutable('2026-08-29T00:00:00+00:00'),
    );
    app(EnrollmentRepository::class)->save($newEnrollment);

    app(NotificationRepository::class)->save(Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $userId,
        channel: NotificationChannel::Mobile,
        category: 'general',
        subject: 'Vieja',
        body: 'Cuerpo',
        sentAt: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
    ));

    $this->getJson('/api/v1/mobile/sync?since=2026-08-15T00:00:00%2B00:00')
        ->assertOk()
        ->assertJsonCount(1, 'data.enrollments')
        ->assertJsonPath('data.enrollments.0.id', $newEnrollment->id()->value())
        ->assertJsonCount(0, 'data.notifications');
});

it('requiere autenticacion para sincronizar', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/mobile/sync')->assertUnauthorized();
});
