<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Services\ExperienceLevelCalculator;

function newExperienceEntry(int $points, ?string $competencyId = null, ?string $userId = null): ExperienceEntry
{
    return ExperienceEntry::record(
        id: (string) Str::uuid(),
        userId: $userId ?? (string) Str::uuid(),
        points: $points,
        competencyId: $competencyId,
        reason: 'Motivo',
        recordedAt: new DateTimeImmutable('now'),
    );
}

it('calcula nivel 1 sin experiencia registrada', function (): void {
    $summary = (new ExperienceLevelCalculator)->summarize('usuario-1', []);

    expect($summary->totalPoints)->toBe(0)
        ->and($summary->generalLevel)->toBe(1)
        ->and($summary->competencies)->toBe([]);
});

it('suma los puntos de todos los registros para el nivel general', function (): void {
    $entries = [
        newExperienceEntry(50),
        newExperienceEntry(30, 'manejo-defensivo'),
        newExperienceEntry(25, 'senalizacion'),
    ];

    $summary = (new ExperienceLevelCalculator)->summarize('usuario-1', $entries);

    expect($summary->totalPoints)->toBe(105)
        ->and($summary->generalLevel)->toBe(2);
});

it('sube de nivel cada 100 puntos acumulados', function (): void {
    $summary = (new ExperienceLevelCalculator)->summarize('usuario-1', [newExperienceEntry(250)]);

    expect($summary->generalLevel)->toBe(3);
});

it('calcula el nivel por competencia de forma independiente al nivel general', function (): void {
    $entries = [
        newExperienceEntry(120, 'manejo-defensivo'),
        newExperienceEntry(30, 'senalizacion'),
    ];

    $summary = (new ExperienceLevelCalculator)->summarize('usuario-1', $entries);

    $manejoDefensivo = collect($summary->competencies)->firstWhere('competencyId', 'manejo-defensivo');
    $senalizacion = collect($summary->competencies)->firstWhere('competencyId', 'senalizacion');

    expect($summary->generalLevel)->toBe(2)
        ->and($manejoDefensivo->totalPoints)->toBe(120)
        ->and($manejoDefensivo->level)->toBe(2)
        ->and($senalizacion->totalPoints)->toBe(30)
        ->and($senalizacion->level)->toBe(1);
});

it('ignora los registros sin competencia al calcular niveles por competencia', function (): void {
    $entries = [
        newExperienceEntry(80),
        newExperienceEntry(20, 'manejo-defensivo'),
    ];

    $summary = (new ExperienceLevelCalculator)->summarize('usuario-1', $entries);

    expect($summary->competencies)->toHaveCount(1)
        ->and($summary->competencies[0]->competencyId)->toBe('manejo-defensivo');
});
