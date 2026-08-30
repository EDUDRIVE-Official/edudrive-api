<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function persistedStudentProfileTestUser(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de perfil',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('guarda y recupera un perfil de estudiante por usuario', function (): void {
    /** @var TestCase $this */
    $user = persistedStudentProfileTestUser();
    $repository = app(StudentProfileRepository::class);

    $updatedAt = new DateTimeImmutable('2026-08-30 10:00:00');
    $repository->save(StudentProfile::restore(
        userId: $user->id(),
        educationLevel: 'Universitario incompleto',
        accessibilityNeeds: 'Requiere más tiempo en exámenes.',
        learningPreferences: 'Video',
        updatedAt: $updatedAt,
    ));

    $persisted = $repository->findByUserId($user->id());

    expect($persisted)->not->toBeNull()
        ->and($persisted?->educationLevel())->toBe('Universitario incompleto')
        ->and($persisted?->accessibilityNeeds())->toBe('Requiere más tiempo en exámenes.')
        ->and($persisted?->learningPreferences())->toBe('Video');
});

it('actualiza el perfil existente al guardar de nuevo en vez de duplicarlo', function (): void {
    /** @var TestCase $this */
    $user = persistedStudentProfileTestUser();
    $repository = app(StudentProfileRepository::class);

    $repository->save(StudentProfile::create(userId: $user->id()));
    $profile = $repository->findByUserId($user->id());
    $profile->update(
        educationLevel: 'Secundaria completa',
        accessibilityNeeds: null,
        learningPreferences: null,
        occurredAt: new DateTimeImmutable,
    );
    $repository->save($profile);

    expect($repository->findByUserId($user->id())?->educationLevel())->toBe('Secundaria completa');
});

it('devuelve null cuando el usuario no tiene perfil', function (): void {
    /** @var TestCase $this */
    $user = persistedStudentProfileTestUser();
    $repository = app(StudentProfileRepository::class);

    expect($repository->findByUserId($user->id()))->toBeNull();
});
