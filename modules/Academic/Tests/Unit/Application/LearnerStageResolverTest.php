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
    ['2019-09-09', 'explore', 'Explorador Vial'],
    ['2015-09-09', 'discover', 'Aventurero Vial'],
    ['2012-09-09', 'understand', 'Aprendiz Vial'],
    ['2009-09-09', 'prepare', 'Aspirante Responsable'],
    ['2004-09-09', 'drive', 'Conductor Responsable'],
    ['1986-09-09', 'perfect', 'Ciudadano Vial Experimentado'],
    ['1956-09-09', 'refresh', 'Ciudadano Vial Activo'],
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
})->with(['2027-09-20', '2023-09-20']);
