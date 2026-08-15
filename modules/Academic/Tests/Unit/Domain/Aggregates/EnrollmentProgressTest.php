<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;

it('inicia sin lecciones completadas', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));

    expect($progress->lessonCompletions())->toBe([])
        ->and($progress->completedLessonIds())->toBe([])
        ->and($progress->totalTimeSpentMinutes())->toBe(0)
        ->and($progress->lastCompletedAt())->toBeNull();
});

it('agrega una leccion completada', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));
    $lessonId = LessonId::fromString((string) Str::uuid());

    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 15);

    expect($progress->completedLessonIds())->toBe([$lessonId->value()])
        ->and($progress->totalTimeSpentMinutes())->toBe(15)
        ->and($progress->lastCompletedAt())->toEqual(new DateTimeImmutable('2026-08-15T10:00:00+00:00'));
});

it('completar la misma leccion dos veces actualiza en vez de duplicar', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));
    $lessonId = LessonId::fromString((string) Str::uuid());

    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 5);
    $progress->completeLesson($lessonId, new DateTimeImmutable('2026-08-15T11:00:00+00:00'), 20);

    expect($progress->lessonCompletions())->toHaveCount(1)
        ->and($progress->totalTimeSpentMinutes())->toBe(20)
        ->and($progress->lastCompletedAt())->toEqual(new DateTimeImmutable('2026-08-15T11:00:00+00:00'));
});

it('suma el tiempo invertido de varias lecciones e ignora los nulos', function (): void {
    $progress = EnrollmentProgress::create(EnrollmentId::fromString((string) Str::uuid()));

    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 10);
    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('2026-08-15T10:00:00+00:00'), null);

    expect($progress->totalTimeSpentMinutes())->toBe(10);
});

it('restaura desde persistencia con las completitudes dadas', function (): void {
    $enrollmentId = EnrollmentId::fromString((string) Str::uuid());
    $completion = LessonCompletion::create(
        LessonId::fromString((string) Str::uuid()),
        new DateTimeImmutable('2026-08-15T10:00:00+00:00'),
        8,
    );

    $progress = EnrollmentProgress::restore($enrollmentId, [$completion]);

    expect($progress->enrollmentId()->equals($enrollmentId))->toBeTrue()
        ->and($progress->lessonCompletions())->toHaveCount(1);
});
