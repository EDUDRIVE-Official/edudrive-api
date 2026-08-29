<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateEnrollmentCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Queries\GetEnrollmentQuery;
use Modules\Academic\Application\Queries\ListEnrollmentsQuery;
use Modules\Academic\Application\Responses\EnrollmentListItemResponse;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Application\UseCases\CreateEnrollmentHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentHandler;
use Modules\Academic\Application\UseCases\ListEnrollmentsHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

final class InMemoryEnrollmentRepository implements EnrollmentRepository
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
        return array_values(array_filter(
            $this->items,
            static fn (Enrollment $enrollment): bool => ($courseId === null || $enrollment->courseId()->equals($courseId))
                && ($userId === null || $enrollment->userId() === $userId)
                && ($organizationId === null || $enrollment->organizationId()?->value() === strtolower(trim($organizationId)))
                && ($status === null || $enrollment->status() === $status)
                && ($source === null || $enrollment->source() === $source),
        ));
    }
}

function enrollmentHandlerCourse(): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('ENH-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de matricula'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

it('crea una matricula individual y devuelve su response', function (): void {
    $repository = new InMemoryEnrollmentRepository;
    $course = enrollmentHandlerCourse();
    $handler = new CreateEnrollmentHandler($repository, app(CourseRepository::class));

    $response = $handler->handle(new CreateEnrollmentCommand(
        courseId: $course->id()->value(),
        userId: (string) Str::uuid(),
        status: 'pending',
        source: 'individual',
        startsAt: '2026-09-01T00:00:00+00:00',
        endsAt: '2026-12-01T00:00:00+00:00',
    ));

    expect($response)->toBeInstanceOf(EnrollmentResponse::class)
        ->and($response->status)->toBe('pending')
        ->and($response->source)->toBe('individual')
        ->and($repository->items)->toHaveCount(1);
});

it('rechaza crear una matricula para un curso inexistente', function (): void {
    $repository = new InMemoryEnrollmentRepository;
    $handler = new CreateEnrollmentHandler($repository, app(CourseRepository::class));

    expect(fn () => $handler->handle(new CreateEnrollmentCommand(
        courseId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
    )))->toThrow(CourseNotFound::class);
});

it('devuelve la matricula existente en vez de fallar ante un reintento (idempotencia)', function (): void {
    $repository = new InMemoryEnrollmentRepository;
    $course = enrollmentHandlerCourse();
    $userId = (string) Str::uuid();
    $existing = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $repository->save($existing);
    $handler = new CreateEnrollmentHandler($repository, app(CourseRepository::class));

    $response = $handler->handle(new CreateEnrollmentCommand(
        courseId: $course->id()->value(),
        userId: $userId,
    ));

    expect($response->id)->toBe($existing->id()->value())
        ->and($repository->items)->toHaveCount(1);
});

it('obtiene y lista matriculas con filtros', function (): void {
    $repository = new InMemoryEnrollmentRepository;
    $course = enrollmentHandlerCourse();
    $userA = (string) Str::uuid();
    $userB = (string) Str::uuid();
    $first = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userA,
        status: EnrollmentStatus::Pending,
        source: EnrollmentSource::Individual,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:00:00+00:00'),
    );
    $second = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userB,
        status: EnrollmentStatus::Completed,
        source: EnrollmentSource::Bulk,
        enrolledAt: new DateTimeImmutable('2026-08-13T10:05:00+00:00'),
    );
    $repository->save($first);
    $repository->save($second);

    $detail = (new GetEnrollmentHandler($repository))->handle(new GetEnrollmentQuery($first->id()->value()));
    $listed = (new ListEnrollmentsHandler($repository))->handle(new ListEnrollmentsQuery(userId: $userB, status: 'completed'));

    expect($detail)->toBeInstanceOf(EnrollmentResponse::class)
        ->and($detail->id)->toBe($first->id()->value())
        ->and($listed)->toHaveCount(1)
        ->and($listed[0])->toBeInstanceOf(EnrollmentListItemResponse::class)
        ->and($listed[0]->userId)->toBe($userB);
});
