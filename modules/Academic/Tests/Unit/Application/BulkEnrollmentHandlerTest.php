<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateBulkEnrollmentsCommand;
use Modules\Academic\Application\Commands\CreateInstitutionalEnrollmentCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\BulkEnrollmentResponse;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Application\UseCases\CreateBulkEnrollmentsHandler;
use Modules\Academic\Application\UseCases\CreateInstitutionalEnrollmentHandler;
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

final class BulkEnrollmentRepository implements EnrollmentRepository
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

function bulkEnrollmentCourse(): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('ENB-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso bulk enrollment'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

it('crea matriculas masivas y devuelve resultado parcial por usuario', function (): void {
    $repository = new BulkEnrollmentRepository;
    $course = bulkEnrollmentCourse();
    $existingUser = (string) Str::uuid();
    $newUser = (string) Str::uuid();
    $repository->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $existingUser,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    ));
    $handler = new CreateBulkEnrollmentsHandler($repository, app(CourseRepository::class));

    $response = $handler->handle(new CreateBulkEnrollmentsCommand(
        courseId: $course->id()->value(),
        userIds: [$existingUser, $newUser],
        status: 'active',
        source: 'bulk',
    ));

    expect($response)->toBeInstanceOf(BulkEnrollmentResponse::class)
        ->and($response->total)->toBe(2)
        ->and($response->created)->toBe(1)
        ->and($response->failed)->toBe(1)
        ->and($response->results)->toHaveCount(2)
        ->and($response->results[0]['created'])->toBeFalse()
        ->and($response->results[1]['created'])->toBeTrue();
});

it('rechaza bulk para un curso inexistente', function (): void {
    $repository = new BulkEnrollmentRepository;
    $handler = new CreateBulkEnrollmentsHandler($repository, app(CourseRepository::class));

    expect(fn () => $handler->handle(new CreateBulkEnrollmentsCommand(
        courseId: (string) Str::uuid(),
        userIds: [(string) Str::uuid()],
    )))->toThrow(CourseNotFound::class);
});

it('crea una matricula institucional y exige organization id', function (): void {
    $repository = new BulkEnrollmentRepository;
    $course = bulkEnrollmentCourse();
    $handler = new CreateInstitutionalEnrollmentHandler($repository, app(CourseRepository::class));

    $response = $handler->handle(new CreateInstitutionalEnrollmentCommand(
        courseId: $course->id()->value(),
        userId: (string) Str::uuid(),
        organizationId: (string) Str::uuid(),
        status: 'active',
    ));

    expect($response)->toBeInstanceOf(EnrollmentResponse::class)
        ->and($response->source)->toBe('institutional')
        ->and($response->organizationId)->not->toBeNull();

    expect(fn () => $handler->handle(new CreateInstitutionalEnrollmentCommand(
        courseId: $course->id()->value(),
        userId: (string) Str::uuid(),
        organizationId: '   ',
        status: 'active',
    )))->toThrow(InvalidEnrollment::class);
});
