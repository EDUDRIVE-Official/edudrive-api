<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentLessonCompletionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentModel;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function enrollmentProgressUser(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario progreso',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedEnrollmentForProgress(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-REPO-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: enrollmentProgressUser(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('guarda y recupera lecciones completadas', function (): void {
    $enrollment = persistedEnrollmentForProgress();
    $lessonId = LessonId::fromString((string) Str::uuid());

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 12);

    $repository = app(EnrollmentProgressRepository::class);
    $repository->save($progress);

    $restored = $repository->findByEnrollmentId($enrollment->id());

    expect($restored->completedLessonIds())->toBe([$lessonId->value()])
        ->and($restored->totalTimeSpentMinutes())->toBe(12);
});

it('actualiza la fila existente en vez de duplicar al completar de nuevo', function (): void {
    $enrollment = persistedEnrollmentForProgress();
    $lessonId = LessonId::fromString((string) Str::uuid());
    $repository = app(EnrollmentProgressRepository::class);

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 5);
    $repository->save($progress);

    $progress = $repository->findByEnrollmentId($enrollment->id());
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T11:00:00+00:00'), 20);
    $repository->save($progress);

    $restored = $repository->findByEnrollmentId($enrollment->id());

    expect($restored->lessonCompletions())->toHaveCount(1)
        ->and($restored->totalTimeSpentMinutes())->toBe(20);
});

it('devuelve un progreso vacio para un enrollment sin completitudes', function (): void {
    $enrollment = persistedEnrollmentForProgress();

    $restored = app(EnrollmentProgressRepository::class)->findByEnrollmentId($enrollment->id());

    expect($restored->completedLessonIds())->toBe([]);
});

it('borra en cascada las completitudes al eliminar el enrollment', function (): void {
    $enrollment = persistedEnrollmentForProgress();
    $repository = app(EnrollmentProgressRepository::class);

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('now'), 5);
    $repository->save($progress);

    EnrollmentModel::query()->where('id', $enrollment->id()->value())->delete();

    expect(EnrollmentLessonCompletionModel::query()->count())->toBe(0);
});
