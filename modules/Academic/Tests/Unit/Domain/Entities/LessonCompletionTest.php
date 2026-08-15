<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Entities\LessonCompletion;
use Modules\Academic\Domain\Exceptions\InvalidLessonCompletion;
use Modules\Academic\Domain\ValueObjects\LessonId;

it('crea una completitud de leccion con tiempo invertido', function (): void {
    $lessonId = LessonId::fromString((string) Str::uuid());
    $completedAt = new DateTimeImmutable('2026-08-15T10:00:00+00:00');

    $completion = LessonCompletion::create($lessonId, $completedAt, 12);

    expect($completion->lessonId()->equals($lessonId))->toBeTrue()
        ->and($completion->completedAt())->toEqual($completedAt)
        ->and($completion->timeSpentMinutes())->toBe(12);
});

it('permite tiempo invertido nulo', function (): void {
    $completion = LessonCompletion::create(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('now'), null);

    expect($completion->timeSpentMinutes())->toBeNull();
});

it('rechaza tiempo invertido negativo', function (): void {
    expect(fn () => LessonCompletion::create(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('now'), -1))
        ->toThrow(InvalidLessonCompletion::class);
});

it('actualiza completedAt y tiempo invertido preservando el lessonId', function (): void {
    $lessonId = LessonId::fromString((string) Str::uuid());
    $completion = LessonCompletion::create($lessonId, new DateTimeImmutable('2026-08-15T09:00:00+00:00'), 5);

    $updated = $completion->withCompletion(new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 20);

    expect($updated->lessonId()->equals($lessonId))->toBeTrue()
        ->and($updated->timeSpentMinutes())->toBe(20);
});

it('rechaza tiempo invertido negativo al actualizar', function (): void {
    $completion = LessonCompletion::create(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('now'), 5);

    expect(fn () => $completion->withCompletion(new DateTimeImmutable('now'), -1))
        ->toThrow(InvalidLessonCompletion::class);
});
