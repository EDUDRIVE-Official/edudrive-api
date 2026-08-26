<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Entities\ExperienceEntry;

it('registra puntos de experiencia con su competencia y motivo', function (): void {
    $recordedAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');

    $entry = ExperienceEntry::record(
        id: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        points: 50,
        competencyId: 'manejo-defensivo',
        reason: 'Completó la sesión práctica sin infracciones.',
        recordedAt: $recordedAt,
    );

    expect($entry->points())->toBe(50)
        ->and($entry->competencyId())->toBe('manejo-defensivo')
        ->and($entry->reason())->toBe('Completó la sesión práctica sin infracciones.')
        ->and($entry->recordedAt())->toBe($recordedAt);
});

it('acepta un registro sin competencia asociada', function (): void {
    $entry = ExperienceEntry::record(
        id: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        points: 10,
        competencyId: null,
        reason: 'Participación general.',
        recordedAt: new DateTimeImmutable('now'),
    );

    expect($entry->competencyId())->toBeNull();
});

it('rechaza puntos negativos', function (): void {
    expect(fn () => ExperienceEntry::record((string) Str::uuid(), (string) Str::uuid(), -5, null, 'Motivo', new DateTimeImmutable('now')))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza puntos en cero', function (): void {
    expect(fn () => ExperienceEntry::record((string) Str::uuid(), (string) Str::uuid(), 0, null, 'Motivo', new DateTimeImmutable('now')))
        ->toThrow(InvalidArgumentException::class);
});
