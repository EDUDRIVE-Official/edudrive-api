<?php

declare(strict_types=1);

use Modules\Academic\Application\Services\LearnerStageResolver;

it('resuelve las etapas de la trayectoria edudrive segun la edad', function (string $birthDate, string $stage, string $identity): void {
    $result = (new LearnerStageResolver)->resolve(
        new DateTimeImmutable($birthDate),
        new DateTimeImmutable('2026-09-09'),
    );

    expect($result['stage'])->toBe($stage)
        ->and($result['identity'])->toBe($identity)
        ->and($result['instruction'])->not->toBeEmpty()
        ->and($result['reflection_prompt'])->not->toBeEmpty()
        ->and($result['practice_mode'])->not->toBeEmpty();
 })->with([
    ['2023-09-09', 'E1', 'DESCUBRO'],
    ['2020-09-09', 'E1', 'DESCUBRO'],
    ['2019-09-09', 'E2', 'COMPRENDO'],
    ['2014-09-09', 'E2', 'COMPRENDO'],
    ['2013-09-09', 'E3', 'DECIDO'],
    ['2010-09-09', 'E3', 'DECIDO'],
    ['2009-09-09', 'E4', 'CONDUZCO'],
    ['1940-09-09', 'E4', 'CONDUZCO'],
]);

it('no atribuye una etapa ni autonomia cuando no existe fecha de nacimiento', function (): void {
    $result = (new LearnerStageResolver)->resolve(null);

    expect($result['stage'])->toBe('universal')
        ->and($result['identity'])->toBe('Ciudadano Vial')
        ->and($result['age_range'])->toBe('Etapa por confirmar')
        ->and($result['requires_guardian'])->toBeTrue();
});

it('no asigna una etapa para fechas futuras o edades fuera del alcance infantil', function (string $birth): void {
    $result = (new LearnerStageResolver)->resolve(new DateTimeImmutable($birth), new DateTimeImmutable('2026-09-20'));
    expect($result['stage'])->toBe('universal')->and($result['requires_guardian'])->toBeTrue();
})->with(['2027-09-20', '2024-09-20']);
