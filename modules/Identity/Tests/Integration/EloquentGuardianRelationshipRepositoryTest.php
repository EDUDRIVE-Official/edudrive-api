<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function persistedGuardianRelationshipTestUser(): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('guarda y recupera una relacion tutor-menor por identificador', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipTestUser();
    $minor = persistedGuardianRelationshipTestUser();
    $repository = app(GuardianRelationshipRepository::class);

    $relationship = GuardianRelationship::create(
        id: (string) Str::uuid(),
        guardianUserId: $guardian->id(),
        minorUserId: $minor->id(),
    );
    $repository->save($relationship);

    $persisted = $repository->findById($relationship->id());

    expect($persisted)->not->toBeNull()
        ->and($persisted?->guardianUserId())->toBe($guardian->id())
        ->and($persisted?->minorUserId())->toBe($minor->id())
        ->and($persisted?->isActive())->toBeTrue();
});

it('encuentra la relacion activa entre un tutor y un menor especificos', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipTestUser();
    $minor = persistedGuardianRelationshipTestUser();
    $repository = app(GuardianRelationshipRepository::class);

    $repository->save(GuardianRelationship::create(
        id: (string) Str::uuid(),
        guardianUserId: $guardian->id(),
        minorUserId: $minor->id(),
    ));

    expect($repository->findActiveByGuardianAndMinor($guardian->id(), $minor->id()))->not->toBeNull();
});

it('no encuentra una relacion activa tras revocarla', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipTestUser();
    $minor = persistedGuardianRelationshipTestUser();
    $repository = app(GuardianRelationshipRepository::class);

    $relationship = GuardianRelationship::create(
        id: (string) Str::uuid(),
        guardianUserId: $guardian->id(),
        minorUserId: $minor->id(),
    );
    $repository->save($relationship);

    $relationship->revoke(new DateTimeImmutable);
    $repository->save($relationship);

    expect($repository->findActiveByGuardianAndMinor($guardian->id(), $minor->id()))->toBeNull()
        ->and($repository->findById($relationship->id())?->isActive())->toBeFalse();
});

it('lista solo las relaciones activas de un tutor', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipTestUser();
    $minorActivo = persistedGuardianRelationshipTestUser();
    $minorRevocado = persistedGuardianRelationshipTestUser();
    $repository = app(GuardianRelationshipRepository::class);

    $repository->save(GuardianRelationship::create(
        id: (string) Str::uuid(),
        guardianUserId: $guardian->id(),
        minorUserId: $minorActivo->id(),
    ));

    $revocada = GuardianRelationship::create(
        id: (string) Str::uuid(),
        guardianUserId: $guardian->id(),
        minorUserId: $minorRevocado->id(),
    );
    $revocada->revoke(new DateTimeImmutable);
    $repository->save($revocada);

    $result = $repository->findActiveByGuardian($guardian->id());

    expect($result)->toHaveCount(1)
        ->and($result[0]->minorUserId())->toBe($minorActivo->id());
});

it('devuelve null cuando la relacion no existe', function (): void {
    /** @var TestCase $this */
    $repository = app(GuardianRelationshipRepository::class);

    expect($repository->findById((string) Str::uuid()))->toBeNull();
});
