<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseVersion;
use Modules\Academic\Domain\Enums\CourseVersionStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseVersionModel;

function persistedVersionCourse(string $id = '019c2b00-0000-7000-8000-000000000301'): Course
{
    $course = Course::create(
        id: CourseId::fromString($id),
        code: CourseCode::fromString('VER-001'),
        title: CourseTitle::fromString('Curso versionado'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

/** @return array{id: string, version_number: int, status: string, snapshot: array<string, mixed>, published_at: string} */
function persistedVersionSnapshot(int $versionNumber): array
{
    return [
        'id' => '019c2b00-0000-7000-8000-0000000004'.str_pad((string) $versionNumber, 2, '0', STR_PAD_LEFT),
        'version_number' => $versionNumber,
        'status' => 'published',
        'snapshot' => ['course' => ['id' => '019c2b00-0000-7000-8000-000000000301'], 'modules' => []],
        'published_at' => '2026-08-10T08:00:00+00:00',
    ];
}

/** @param array{id: string, version_number: int, status: string, snapshot: array<string, mixed>, published_at: string} $row */
function persistedVersionFromRow(array $row): CourseVersion
{
    return CourseVersion::create(
        id: $row['id'],
        courseId: CourseId::fromString('019c2b00-0000-7000-8000-000000000301'),
        versionNumber: $row['version_number'],
        snapshot: $row['snapshot'],
        publishedAt: new DateTimeImmutable($row['published_at']),
    );
}

it('guarda y recupera una version con su snapshot canonico', function (): void {
    $repository = app(CourseVersionRepository::class);
    $course = persistedVersionCourse();
    $version = CourseVersion::create(
        id: '019c2b00-0000-7000-8000-000000000401',
        courseId: $course->id(),
        versionNumber: 1,
        snapshot: ['course' => ['id' => $course->id()->value()], 'modules' => [['id' => 'mod-1']]],
        publishedAt: new DateTimeImmutable('2026-08-10T08:00:00+00:00'),
    );

    $repository->save($version);
    $stored = $repository->findByNumber($course->id(), 1);

    expect($stored)->not->toBeNull()
        ->and($stored?->id())->toBe($version->id())
        ->and($stored?->versionNumber())->toBe(1)
        ->and($stored?->status())->toBe(CourseVersionStatus::Published)
        ->and($stored?->snapshot())->toBe(['course' => ['id' => $course->id()->value()], 'modules' => [['id' => 'mod-1']]])
        ->and($stored?->publishedAt()->format(DATE_ATOM))->toBe('2026-08-10T08:00:00+00:00')
        ->and($stored?->archivedAt())->toBeNull();
});

it('lista las versiones de un curso ordenadas por numero', function (): void {
    $repository = app(CourseVersionRepository::class);
    $course = persistedVersionCourse();

    foreach ([3, 1, 2] as $versionNumber) {
        $repository->save(persistedVersionFromRow(persistedVersionSnapshot($versionNumber)));
    }

    $versions = $repository->allForCourse($course->id());

    expect(array_map(static fn (CourseVersion $version): int => $version->versionNumber(), $versions))
        ->toBe([1, 2, 3]);
});

it('calcula el siguiente numero de version como maximo mas uno', function (): void {
    $repository = app(CourseVersionRepository::class);
    $course = persistedVersionCourse();

    expect($repository->nextVersionNumber($course->id()))->toBe(1);

    $repository->save(persistedVersionFromRow(persistedVersionSnapshot(1)));
    $repository->save(persistedVersionFromRow(persistedVersionSnapshot(3)));

    expect($repository->nextVersionNumber($course->id()))->toBe(4);
});

it('impone la unicidad de course_id con version_number', function (): void {
    $repository = app(CourseVersionRepository::class);
    $course = persistedVersionCourse();
    $first = CourseVersion::create(
        id: '019c2b00-0000-7000-8000-000000000402',
        courseId: $course->id(),
        versionNumber: 1,
        snapshot: ['course' => []],
        publishedAt: new DateTimeImmutable('2026-08-10T08:00:00+00:00'),
    );
    $duplicate = CourseVersion::create(
        id: '019c2b00-0000-7000-8000-000000000403',
        courseId: $course->id(),
        versionNumber: 1,
        snapshot: ['course' => []],
        publishedAt: new DateTimeImmutable('2026-08-10T09:00:00+00:00'),
    );

    $repository->save($first);

    expect(fn () => $repository->save($duplicate))->toThrow(QueryException::class)
        ->and(DB::table('academic_course_versions')->where('course_id', $course->id()->value())->count())->toBe(1);
});

it('elimina las versiones en cascada al eliminar el curso', function (): void {
    $repository = app(CourseVersionRepository::class);
    $course = persistedVersionCourse();
    $repository->save(persistedVersionFromRow(persistedVersionSnapshot(1)));

    DB::table('academic_courses')->where('id', $course->id()->value())->delete();

    expect(CourseVersionModel::query()->where('course_id', $course->id()->value())->count())->toBe(0);
});

it('devuelve versiones vacias para un curso sin historial', function (): void {
    $repository = app(CourseVersionRepository::class);
    $course = persistedVersionCourse();

    expect($repository->allForCourse($course->id()))->toBe([])
        ->and($repository->findByNumber($course->id(), 1))->toBeNull();
});

it('carga el historial con una consulta por curso sin N+1', function (): void {
    $repository = app(CourseVersionRepository::class);
    $course = persistedVersionCourse();
    foreach ([1, 2, 3] as $versionNumber) {
        $repository->save(persistedVersionFromRow(persistedVersionSnapshot($versionNumber)));
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $versions = $repository->allForCourse($course->id());
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($versions)->toHaveCount(3)
        ->and($queryCount)->toBeLessThanOrEqual(2);
});
