<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\ActivateEnrollmentCommand;
use Modules\Academic\Application\Commands\CancelEnrollmentCommand;
use Modules\Academic\Application\Commands\CompleteEnrollmentCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Application\UseCases\ActivateEnrollmentHandler;
use Modules\Academic\Application\UseCases\CancelEnrollmentHandler;
use Modules\Academic\Application\UseCases\CompleteEnrollmentHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final class EnrollmentLifecycleRepository implements EnrollmentRepository
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
        foreach ($this->items as $enrollment) {
            if ($enrollment->courseId()->equals($courseId)
                && $enrollment->userId() === $userId
                && in_array($enrollment->status(), [EnrollmentStatus::Pending, EnrollmentStatus::Active], true)
            ) {
                return $enrollment;
            }
        }

        return null;
    }

    public function all(
        ?CourseId $courseId = null,
        ?string $userId = null,
        ?string $organizationId = null,
        ?EnrollmentStatus $status = null,
        ?EnrollmentSource $source = null,
    ): array {
        return array_values($this->items);
    }
}

function enrollmentLifecycleCourse(): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('ENL-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso lifecycle'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

it('activa una matricula pending', function (): void {
    $repository = new EnrollmentLifecycleRepository;
    $course = enrollmentLifecycleCourse();
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Pending,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $repository->save($enrollment);

    $response = (new ActivateEnrollmentHandler($repository))->handle(new ActivateEnrollmentCommand($enrollment->id()->value()));

    expect($response)->toBeInstanceOf(EnrollmentResponse::class)
        ->and($response->status)->toBe('active');
});

it('completa una matricula activa', function (): void {
    $repository = new EnrollmentLifecycleRepository;
    $course = enrollmentLifecycleCourse();
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $repository->save($enrollment);

    $response = (new CompleteEnrollmentHandler($repository))->handle(new CompleteEnrollmentCommand($enrollment->id()->value()));

    expect($response)->toBeInstanceOf(EnrollmentResponse::class)
        ->and($response->status)->toBe('completed');
});

it('cancela una matricula activa', function (): void {
    $repository = new EnrollmentLifecycleRepository;
    $course = enrollmentLifecycleCourse();
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $repository->save($enrollment);

    $response = (new CancelEnrollmentHandler($repository))->handle(new CancelEnrollmentCommand($enrollment->id()->value()));

    expect($response)->toBeInstanceOf(EnrollmentResponse::class)
        ->and($response->status)->toBe('canceled');
});

it('rechaza lifecycle sobre matricula inexistente', function (): void {
    $repository = new EnrollmentLifecycleRepository;

    expect(fn () => (new ActivateEnrollmentHandler($repository))->handle(new ActivateEnrollmentCommand((string) Str::uuid())))
        ->toThrow(EnrollmentNotFound::class)
        ->and(fn () => (new CompleteEnrollmentHandler($repository))->handle(new CompleteEnrollmentCommand((string) Str::uuid())))
        ->toThrow(EnrollmentNotFound::class)
        ->and(fn () => (new CancelEnrollmentHandler($repository))->handle(new CancelEnrollmentCommand((string) Str::uuid())))
        ->toThrow(EnrollmentNotFound::class);
});

it('rechaza transiciones invalidas segun el estado actual', function (): void {
    $repository = new EnrollmentLifecycleRepository;
    $course = enrollmentLifecycleCourse();
    $completed = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Completed,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $canceled = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: (string) Str::uuid(),
        status: EnrollmentStatus::Canceled,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $repository->save($completed);
    $repository->save($canceled);

    expect(fn () => (new ActivateEnrollmentHandler($repository))->handle(new ActivateEnrollmentCommand($completed->id()->value())))
        ->toThrow(InvalidEnrollment::class)
        ->and(fn () => (new CompleteEnrollmentHandler($repository))->handle(new CompleteEnrollmentCommand($canceled->id()->value())))
        ->toThrow(InvalidEnrollment::class);
});
