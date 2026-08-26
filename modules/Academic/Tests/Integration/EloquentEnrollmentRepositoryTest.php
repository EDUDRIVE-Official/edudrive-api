<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

function enrollmentRepoCourse(string $code): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString($code),
        title: CourseTitle::fromString('Curso '.$code),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

function enrollmentRepoUser(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario matricula',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera una matricula por identificador', function (): void {
    $repository = app(EloquentEnrollmentRepository::class);
    $course = enrollmentRepoCourse('ENR-'.strtoupper((string) Str::random(4)));
    $userId = enrollmentRepoUser();

    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId,
        status: EnrollmentStatus::Pending,
        source: EnrollmentSource::Individual,
        startsAt: new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        endsAt: new DateTimeImmutable('2026-12-01T00:00:00+00:00'),
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );

    $repository->save($enrollment);

    $stored = $repository->findById($enrollment->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->id()->equals($enrollment->id()))->toBeTrue()
        ->and($stored?->courseId()->equals($course->id()))->toBeTrue()
        ->and($stored?->status())->toBe(EnrollmentStatus::Pending)
        ->and($stored?->source())->toBe(EnrollmentSource::Individual)
        ->and($stored?->startsAt()?->format(DATE_ATOM))->toBe('2026-09-01T00:00:00+00:00')
        ->and($stored?->endsAt()?->format(DATE_ATOM))->toBe('2026-12-01T00:00:00+00:00');
});

it('lista matriculas filtradas por curso usuario organizacion estado y source', function (): void {
    $repository = app(EloquentEnrollmentRepository::class);
    $courseA = enrollmentRepoCourse('ENA-'.strtoupper((string) Str::random(4)));
    $courseB = enrollmentRepoCourse('ENB-'.strtoupper((string) Str::random(4)));
    $userA = enrollmentRepoUser();
    $userB = enrollmentRepoUser();
    $organizationId = OrganizationId::fromString((string) Str::uuid());

    $repository->save(Enrollment::create(
        EnrollmentId::fromString((string) Str::uuid()),
        $courseA->id(),
        $userA,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    ));
    $repository->save(Enrollment::create(
        EnrollmentId::fromString((string) Str::uuid()),
        $courseA->id(),
        $userB,
        $organizationId,
        EnrollmentStatus::Pending,
        EnrollmentSource::Institutional,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:05:00+00:00'),
    ));
    $repository->save(Enrollment::create(
        EnrollmentId::fromString((string) Str::uuid()),
        $courseB->id(),
        $userB,
        status: EnrollmentStatus::Completed,
        source: EnrollmentSource::Bulk,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:10:00+00:00'),
    ));

    expect($repository->all())->toHaveCount(3)
        ->and($repository->all(courseId: $courseA->id()))->toHaveCount(2)
        ->and($repository->all(userId: $userA))->toHaveCount(1)
        ->and($repository->all(organizationId: $organizationId->value()))->toHaveCount(1)
        ->and($repository->all(status: EnrollmentStatus::Completed))->toHaveCount(1)
        ->and($repository->all(source: EnrollmentSource::Institutional))->toHaveCount(1);
});

it('encuentra la matricula activa o pending de un curso y usuario', function (): void {
    $repository = app(EloquentEnrollmentRepository::class);
    $course = enrollmentRepoCourse('ENF-'.strtoupper((string) Str::random(4)));
    $userId = enrollmentRepoUser();

    $enrollment = Enrollment::create(
        EnrollmentId::fromString((string) Str::uuid()),
        $course->id(),
        $userId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $repository->save($enrollment);

    $stored = $repository->findActiveOrPendingFor($course->id(), $userId);

    expect($stored)->not->toBeNull()
        ->and($stored?->id()->equals($enrollment->id()))->toBeTrue();
});

it('actualiza el estado de una matricula existente al guardar de nuevo', function (): void {
    $repository = app(EloquentEnrollmentRepository::class);
    $course = enrollmentRepoCourse('ENU-'.strtoupper((string) Str::random(4)));
    $userId = enrollmentRepoUser();

    $enrollment = Enrollment::create(
        EnrollmentId::fromString((string) Str::uuid()),
        $course->id(),
        $userId,
        status: EnrollmentStatus::Pending,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $repository->save($enrollment);

    $enrollment->activate();
    $repository->save($enrollment);

    $stored = $repository->findById($enrollment->id());
    expect($stored?->status())->toBe(EnrollmentStatus::Active);
});
