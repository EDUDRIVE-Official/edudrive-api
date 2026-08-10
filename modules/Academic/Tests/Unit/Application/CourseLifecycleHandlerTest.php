<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\ReopenCourseCommand;
use Modules\Academic\Application\Commands\SendCourseBackToDraftCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\CourseVersionNotFound;
use Modules\Academic\Application\Queries\GetCourseVersionQuery;
use Modules\Academic\Application\Queries\ListCourseVersionsQuery;
use Modules\Academic\Application\UseCases\ApproveCourseHandler;
use Modules\Academic\Application\UseCases\GetCourseVersionHandler;
use Modules\Academic\Application\UseCases\ListCourseVersionsHandler;
use Modules\Academic\Application\UseCases\ReopenCourseHandler;
use Modules\Academic\Application\UseCases\SendCourseBackToDraftHandler;
use Modules\Academic\Application\UseCases\SubmitCourseForReviewHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseVersion;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Enums\CourseVersionStatus;
use Modules\Academic\Domain\Exceptions\CourseCannotBeReopened;
use Modules\Academic\Domain\Exceptions\CourseReviewStateInvalid;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

final class Eng029LifecycleCourseRepository implements CourseRepository
{
    public int $atomicUpdateCalls = 0;

    public int $saveCalls = 0;

    /** @var array<string, Course> */
    private array $courses = [];

    /** @param list<Course> $courses */
    public function __construct(array $courses = [])
    {
        foreach ($courses as $course) {
            $this->courses[$course->id()->value()] = $course;
        }
    }

    public function save(Course $course): void
    {
        $this->saveCalls++;
        $this->courses[$course->id()->value()] = $course;
    }

    public function updateAtomically(CourseId $id, Closure $mutation): ?Course
    {
        $course = $this->findById($id);

        if ($course === null) {
            return null;
        }

        $this->atomicUpdateCalls++;
        $candidate = clone $course;
        $mutation($candidate);
        $this->courses[$id->value()] = $candidate;

        return $candidate;
    }

    public function updateAtomicallyWithContentCoverage(CourseId $id, Closure $mutation): ?Course
    {
        return $this->updateAtomically($id, $mutation);
    }

    public function findById(CourseId $id): ?Course
    {
        return $this->courses[$id->value()] ?? null;
    }

    public function findByCode(CourseCode $code): ?Course
    {
        foreach ($this->courses as $course) {
            if ($course->code()->equals($code)) {
                return $course;
            }
        }

        return null;
    }

    public function existsByCode(CourseCode $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    public function all(): array
    {
        return array_values($this->courses);
    }
}

final class Eng029LifecycleVersionRepository implements CourseVersionRepository
{
    /** @var array<string, list<CourseVersion>> */
    private array $versions = [];

    public function save(CourseVersion $version): void
    {
        $courseId = $version->courseId()->value();
        $this->versions[$courseId] ??= [];
        $this->versions[$courseId][] = $version;
    }

    /** @return list<CourseVersion> */
    public function allForCourse(CourseId $id): array
    {
        return $this->versions[$id->value()] ?? [];
    }

    public function findByNumber(CourseId $id, int $versionNumber): ?CourseVersion
    {
        foreach ($this->allForCourse($id) as $version) {
            if ($version->versionNumber() === $versionNumber) {
                return $version;
            }
        }

        return null;
    }

    public function nextVersionNumber(CourseId $id): int
    {
        return count($this->allForCourse($id)) + 1;
    }
}

/** @param list<CourseVersion> $versions */
function eng029VersionRepository(array $versions = []): Eng029LifecycleVersionRepository
{
    $repository = new Eng029LifecycleVersionRepository;

    foreach ($versions as $version) {
        $repository->save($version);
    }

    return $repository;
}

function eng029LifecycleCourse(string $status = 'draft'): Course
{
    $course = Course::create(
        id: CourseId::fromString('019c2b00-0000-7000-8000-000000000101'),
        code: CourseCode::fromString('ENG029-01'),
        title: CourseTitle::fromString('Curso de ciclo de vida'),
    );

    if ($status === 'under_review') {
        $course->submitForReview();
    }

    if ($status === 'approved') {
        $course->submitForReview();
        $course->approve();
    }

    return $course;
}

function eng029PublishedLifecycleCourse(): Course
{
    $course = eng029LifecycleCourse();
    $course->replaceCurriculum(validAggregateCurriculum());
    $course->submitForReview();
    $course->approve();
    $course->publish(new DateTimeImmutable('2026-08-10T08:00:00+00:00'), validAggregateCoverage());

    return $course;
}

it('envia un curso a revision con una mutacion atomica', function (): void {
    $course = eng029LifecycleCourse();
    $courses = new Eng029LifecycleCourseRepository([$course]);

    $response = (new SubmitCourseForReviewHandler($courses))->handle(
        new SubmitCourseForReviewCommand($course->id()->value()),
    );

    expect($response->toArray())->toBe([
        'id' => $course->id()->value(),
        'code' => 'ENG029-01',
        'title' => 'Curso de ciclo de vida',
        'status' => 'under_review',
    ])->and($courses->atomicUpdateCalls)->toBe(1)
        ->and($courses->saveCalls)->toBe(0)
        ->and($courses->findById($course->id())?->status())->toBe(CourseStatus::UnderReview);
});

it('aprueba un curso en revision con una mutacion atomica', function (): void {
    $course = eng029LifecycleCourse('under_review');
    $courses = new Eng029LifecycleCourseRepository([$course]);

    $response = (new ApproveCourseHandler($courses))->handle(
        new ApproveCourseCommand($course->id()->value()),
    );

    expect($response->toArray()['status'])->toBe('approved')
        ->and($courses->findById($course->id())?->status())->toBe(CourseStatus::Approved);
});

it('devuelve a borrador un curso en revision o aprobado', function (string $initialStatus): void {
    $course = eng029LifecycleCourse($initialStatus);
    $courses = new Eng029LifecycleCourseRepository([$course]);

    $response = (new SendCourseBackToDraftHandler($courses))->handle(
        new SendCourseBackToDraftCommand($course->id()->value()),
    );

    expect($response->toArray()['status'])->toBe('draft')
        ->and($courses->findById($course->id())?->status())->toBe(CourseStatus::Draft);
})->with([
    'en revision' => fn (): string => 'under_review',
    'aprobado' => fn (): string => 'approved',
]);

it('reabre un curso publicado a borrador', function (): void {
    $course = eng029PublishedLifecycleCourse();
    $courses = new Eng029LifecycleCourseRepository([$course]);

    $response = (new ReopenCourseHandler($courses))->handle(
        new ReopenCourseCommand($course->id()->value()),
    );

    expect($response->toArray()['status'])->toBe('draft')
        ->and($courses->findById($course->id())?->status())->toBe(CourseStatus::Draft)
        ->and($courses->findById($course->id())?->publishedAt())->toBeNull();
});

it('lanza 404 al mutar un curso inexistente', function (): void {
    $courses = new Eng029LifecycleCourseRepository;

    try {
        (new SubmitCourseForReviewHandler($courses))->handle(
            new SubmitCourseForReviewCommand('019c2b00-0000-7000-8000-000000000999'),
        );

        test()->fail('Se esperaba CourseNotFound.');
    } catch (CourseNotFound $exception) {
        expect($exception->statusCode())->toBe(404)
            ->and($exception->errorCode())->toBe('COURSE_NOT_FOUND');
    }

    expect($courses->atomicUpdateCalls)->toBe(0);
});

it('propaga el error 422 cuando la transicion de estado es ilegal', function (): void {
    $course = eng029LifecycleCourse();
    $courses = new Eng029LifecycleCourseRepository([$course]);

    expect(fn () => (new ApproveCourseHandler($courses))->handle(
        new ApproveCourseCommand($course->id()->value()),
    ))->toThrow(CourseReviewStateInvalid::class, 'El curso no se encuentra en el estado requerido para esta accion.');

    expect($courses->atomicUpdateCalls)->toBe(1)
        ->and($courses->findById($course->id())?->status())->toBe(CourseStatus::Draft);
});

it('propaga el error 422 al reabrir un curso no publicado', function (): void {
    $course = eng029LifecycleCourse('approved');
    $courses = new Eng029LifecycleCourseRepository([$course]);

    expect(fn () => (new ReopenCourseHandler($courses))->handle(
        new ReopenCourseCommand($course->id()->value()),
    ))->toThrow(CourseCannotBeReopened::class, 'Solo un curso publicado puede reabrirse.');
});

it('lista las versiones publicadas de un curso', function (): void {
    $course = eng029PublishedLifecycleCourse();
    $courses = new Eng029LifecycleCourseRepository([$course]);

    $version = CourseVersion::create(
        id: '019c2b00-0000-7000-8000-000000000201',
        courseId: $course->id(),
        versionNumber: 1,
        snapshot: ['course' => ['id' => $course->id()->value()]],
        publishedAt: new DateTimeImmutable('2026-08-10T08:00:00+00:00'),
    );
    $versions = eng029VersionRepository([$version]);

    $response = (new ListCourseVersionsHandler($courses, $versions))->handle(
        new ListCourseVersionsQuery($course->id()->value()),
    );

    expect($response)->toHaveCount(1)
        ->and($response[0]->toArray())->toBe([
            'version_number' => 1,
            'status' => 'published',
            'published_at' => '2026-08-10T08:00:00+00:00',
            'archived_at' => null,
        ]);
});

it('lanza 404 al listar versiones de un curso inexistente', function (): void {
    $courses = new Eng029LifecycleCourseRepository;
    $versions = eng029VersionRepository();
    $courseId = '019c2b00-0000-7000-8000-000000000999';

    expect(fn () => (new ListCourseVersionsHandler($courses, $versions))->handle(
        new ListCourseVersionsQuery($courseId),
    ))->toThrow(CourseNotFound::class);
});

it('entrega el snapshot de una version existente', function (): void {
    $course = eng029PublishedLifecycleCourse();
    $courses = new Eng029LifecycleCourseRepository([$course]);
    $snapshot = ['course' => ['id' => $course->id()->value()], 'modules' => []];
    $version = CourseVersion::restore(
        id: '019c2b00-0000-7000-8000-000000000202',
        courseId: $course->id(),
        versionNumber: 2,
        status: CourseVersionStatus::Published,
        snapshot: $snapshot,
        publishedAt: new DateTimeImmutable('2026-08-11T08:00:00+00:00'),
        archivedAt: null,
    );
    $versions = eng029VersionRepository([$version]);

    $response = (new GetCourseVersionHandler($courses, $versions))->handle(
        new GetCourseVersionQuery($course->id()->value(), 2),
    );

    expect($response->toArray()['version_number'])->toBe(2)
        ->and($response->toArray()['snapshot'])->toBe($snapshot);
});

it('lanza 404 al consultar una version inexistente', function (): void {
    $course = eng029LifecycleCourse('approved');
    $courses = new Eng029LifecycleCourseRepository([$course]);
    $versions = eng029VersionRepository();

    try {
        (new GetCourseVersionHandler($courses, $versions))->handle(
            new GetCourseVersionQuery($course->id()->value(), 5),
        );

        test()->fail('Se esperaba CourseVersionNotFound.');
    } catch (CourseVersionNotFound $exception) {
        expect($exception->statusCode())->toBe(404)
            ->and($exception->errorCode())->toBe('COURSE_VERSION_NOT_FOUND');
    }
});
