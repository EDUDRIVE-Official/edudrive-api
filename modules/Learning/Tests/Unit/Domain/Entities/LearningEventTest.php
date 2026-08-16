<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Learning\Domain\Entities\LearningEvent;
use Modules\Learning\Domain\ValueObjects\LearningEventId;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

it('crea un evento de aprendizaje inmutable con sus datos', function (): void {
    $id = LearningEventId::fromString((string) Str::uuid());
    $occurredAt = new DateTimeImmutable('2026-08-16T10:00:00+00:00');

    $event = LearningEvent::create(
        id: $id,
        enrollmentId: 'enrollment-1',
        userId: 'user-1',
        courseId: 'course-1',
        verb: LearningVerb::LessonCompleted,
        subjectId: 'lesson-1',
        occurredAt: $occurredAt,
        evidence: ['time_spent_minutes' => 12],
    );

    expect($event->id()->equals($id))->toBeTrue()
        ->and($event->enrollmentId())->toBe('enrollment-1')
        ->and($event->userId())->toBe('user-1')
        ->and($event->courseId())->toBe('course-1')
        ->and($event->verb())->toBe(LearningVerb::LessonCompleted)
        ->and($event->subjectId())->toBe('lesson-1')
        ->and($event->occurredAt())->toBe($occurredAt)
        ->and($event->evidence())->toBe(['time_spent_minutes' => 12]);
});

it('rechaza un identificador de evento que no es un uuid valido', function (): void {
    expect(fn () => LearningEventId::fromString('no-es-un-uuid'))
        ->toThrow(InvalidArgumentException::class);
});

it('expone los dos verbos soportados', function (): void {
    expect(LearningVerb::LessonCompleted->value)->toBe('lesson_completed')
        ->and(LearningVerb::ExamAttemptSubmitted->value)->toBe('exam_attempt_submitted');
});
