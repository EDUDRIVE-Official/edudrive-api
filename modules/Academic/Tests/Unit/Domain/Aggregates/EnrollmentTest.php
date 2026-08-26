<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

it('crea una matricula individual valida', function (): void {
    $enrolledAt = new DateTimeImmutable('2026-08-13T10:00:00+00:00');

    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Pending,
        source: EnrollmentSource::Individual,
        startsAt: new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        endsAt: new DateTimeImmutable('2026-12-01T00:00:00+00:00'),
        enrolledAt: $enrolledAt,
    );

    expect($enrollment->status())->toBe(EnrollmentStatus::Pending)
        ->and($enrollment->source())->toBe(EnrollmentSource::Individual)
        ->and($enrollment->organizationId())->toBeNull()
        ->and($enrollment->startsAt()?->format(DATE_ATOM))->toBe('2026-09-01T00:00:00+00:00')
        ->and($enrollment->endsAt()?->format(DATE_ATOM))->toBe('2026-12-01T00:00:00+00:00')
        ->and($enrollment->enrolledAt())->toBe($enrolledAt);
});

it('crea una matricula institucional valida', function (): void {
    $organizationId = OrganizationId::fromString((string) Str::uuid());

    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        organizationId: $organizationId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Institutional,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );

    expect($enrollment->organizationId()?->equals($organizationId))->toBeTrue()
        ->and($enrollment->source())->toBe(EnrollmentSource::Institutional);
});

it('rechaza una matricula institucional sin organizacion', function (): void {
    expect(fn () => Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Institutional,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    ))->toThrow(InvalidEnrollment::class);
});

it('rechaza ends_at anterior a starts_at', function (): void {
    expect(fn () => Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Pending,
        source: EnrollmentSource::Bulk,
        startsAt: new DateTimeImmutable('2026-12-01T00:00:00+00:00'),
        endsAt: new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    ))->toThrow(InvalidEnrollment::class);
});

it('permite transiciones pending a active y luego a completed', function (): void {
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Pending,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );

    $enrollment->activate();
    $enrollment->complete();

    expect($enrollment->status())->toBe(EnrollmentStatus::Completed);
});

it('permite cancelar una matricula activa', function (): void {
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );

    $enrollment->cancel();

    expect($enrollment->status())->toBe(EnrollmentStatus::Canceled);
});

it('rechaza activar una matricula completada', function (): void {
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Completed,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );

    expect(fn () => $enrollment->activate())
        ->toThrow(InvalidEnrollment::class);
});

it('rechaza completar una matricula cancelada', function (): void {
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Canceled,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );

    expect(fn () => $enrollment->complete())
        ->toThrow(InvalidEnrollment::class);
});
