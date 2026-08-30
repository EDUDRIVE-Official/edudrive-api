<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\StudentProfile;

it('crea un perfil de estudiante vacio', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-30 10:00:00');

    $profile = StudentProfile::create(userId: 'user-1', occurredAt: $createdAt);

    expect($profile->userId())->toBe('user-1')
        ->and($profile->educationLevel())->toBeNull()
        ->and($profile->accessibilityNeeds())->toBeNull()
        ->and($profile->learningPreferences())->toBeNull()
        ->and($profile->updatedAt())->toEqual($createdAt);
});

it('reconstruye un perfil desde persistencia', function (): void {
    $updatedAt = new DateTimeImmutable('2026-08-30 10:00:00');

    $profile = StudentProfile::restore(
        userId: 'user-1',
        educationLevel: 'Universitario incompleto',
        accessibilityNeeds: 'Requiere más tiempo en exámenes.',
        learningPreferences: 'Prefiere contenido en video.',
        updatedAt: $updatedAt,
    );

    expect($profile->educationLevel())->toBe('Universitario incompleto')
        ->and($profile->accessibilityNeeds())->toBe('Requiere más tiempo en exámenes.')
        ->and($profile->learningPreferences())->toBe('Prefiere contenido en video.')
        ->and($profile->updatedAt())->toEqual($updatedAt);
});

it('actualiza los campos del perfil', function (): void {
    $profile = StudentProfile::create(userId: 'user-1', occurredAt: new DateTimeImmutable('2026-08-30 10:00:00'));
    $updatedAt = new DateTimeImmutable('2026-08-30 11:00:00');

    $profile->update(
        educationLevel: 'Secundaria completa',
        accessibilityNeeds: 'Ninguna',
        learningPreferences: 'Aprendizaje auditivo',
        occurredAt: $updatedAt,
    );

    expect($profile->educationLevel())->toBe('Secundaria completa')
        ->and($profile->accessibilityNeeds())->toBe('Ninguna')
        ->and($profile->learningPreferences())->toBe('Aprendizaje auditivo')
        ->and($profile->updatedAt())->toEqual($updatedAt);
});

it('permite limpiar un campo pasando null', function (): void {
    $profile = StudentProfile::restore(
        userId: 'user-1',
        educationLevel: 'Secundaria completa',
        accessibilityNeeds: 'Ninguna',
        learningPreferences: 'Aprendizaje auditivo',
        updatedAt: new DateTimeImmutable('2026-08-30 10:00:00'),
    );

    $profile->update(
        educationLevel: null,
        accessibilityNeeds: null,
        learningPreferences: null,
        occurredAt: new DateTimeImmutable('2026-08-30 11:00:00'),
    );

    expect($profile->educationLevel())->toBeNull()
        ->and($profile->accessibilityNeeds())->toBeNull()
        ->and($profile->learningPreferences())->toBeNull();
});
