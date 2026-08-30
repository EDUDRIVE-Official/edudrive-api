<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\TeacherProfile;

it('crea un perfil de docente vacio', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-30 10:00:00');

    $profile = TeacherProfile::create(userId: 'user-1', occurredAt: $createdAt);

    expect($profile->userId())->toBe('user-1')
        ->and($profile->specialties())->toBeNull()
        ->and($profile->certifications())->toBeNull()
        ->and($profile->updatedAt())->toEqual($createdAt);
});

it('reconstruye un perfil desde persistencia', function (): void {
    $updatedAt = new DateTimeImmutable('2026-08-30 10:00:00');

    $profile = TeacherProfile::restore(
        userId: 'user-1',
        specialties: 'Manejo defensivo, motocicletas',
        certifications: 'Instructor certificado INA',
        updatedAt: $updatedAt,
    );

    expect($profile->specialties())->toBe('Manejo defensivo, motocicletas')
        ->and($profile->certifications())->toBe('Instructor certificado INA')
        ->and($profile->updatedAt())->toEqual($updatedAt);
});

it('actualiza los campos del perfil', function (): void {
    $profile = TeacherProfile::create(userId: 'user-1', occurredAt: new DateTimeImmutable('2026-08-30 10:00:00'));
    $updatedAt = new DateTimeImmutable('2026-08-30 11:00:00');

    $profile->update(
        specialties: 'Manejo defensivo',
        certifications: 'Licenciatura en Pedagogía',
        occurredAt: $updatedAt,
    );

    expect($profile->specialties())->toBe('Manejo defensivo')
        ->and($profile->certifications())->toBe('Licenciatura en Pedagogía')
        ->and($profile->updatedAt())->toEqual($updatedAt);
});

it('permite limpiar un campo pasando null', function (): void {
    $profile = TeacherProfile::restore(
        userId: 'user-1',
        specialties: 'Manejo defensivo',
        certifications: 'Instructor certificado INA',
        updatedAt: new DateTimeImmutable('2026-08-30 10:00:00'),
    );

    $profile->update(
        specialties: null,
        certifications: null,
        occurredAt: new DateTimeImmutable('2026-08-30 11:00:00'),
    );

    expect($profile->specialties())->toBeNull()
        ->and($profile->certifications())->toBeNull();
});
