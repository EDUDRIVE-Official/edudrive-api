<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\TeacherProfile;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\TeacherProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function persistedTeacherProfileTestUser(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario docente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('guarda y recupera un perfil de docente por usuario', function (): void {
    /** @var TestCase $this */
    $user = persistedTeacherProfileTestUser();
    $repository = app(TeacherProfileRepository::class);

    $updatedAt = new DateTimeImmutable('2026-08-30 10:00:00');
    $repository->save(TeacherProfile::restore(
        userId: $user->id(),
        specialties: 'Manejo defensivo, motocicletas',
        certifications: 'Instructor certificado INA',
        updatedAt: $updatedAt,
    ));

    $persisted = $repository->findByUserId($user->id());

    expect($persisted)->not->toBeNull()
        ->and($persisted?->specialties())->toBe('Manejo defensivo, motocicletas')
        ->and($persisted?->certifications())->toBe('Instructor certificado INA');
});

it('actualiza el perfil existente al guardar de nuevo en vez de duplicarlo', function (): void {
    /** @var TestCase $this */
    $user = persistedTeacherProfileTestUser();
    $repository = app(TeacherProfileRepository::class);

    $repository->save(TeacherProfile::create(userId: $user->id()));
    $profile = $repository->findByUserId($user->id());
    $profile->update(
        specialties: 'Manejo defensivo',
        certifications: null,
        occurredAt: new DateTimeImmutable,
    );
    $repository->save($profile);

    expect($repository->findByUserId($user->id())?->specialties())->toBe('Manejo defensivo');
});

it('devuelve null cuando el usuario no tiene perfil', function (): void {
    /** @var TestCase $this */
    $user = persistedTeacherProfileTestUser();
    $repository = app(TeacherProfileRepository::class);

    expect($repository->findByUserId($user->id()))->toBeNull();
});
