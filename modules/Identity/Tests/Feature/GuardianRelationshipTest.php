<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

function persistedGuardianRelationshipFeatureUser(bool $minor = false): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: $minor ? 'Menor de prueba' : 'Adulto de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable($minor ? '2015-01-01' : '1990-01-01'),
    );
    app(UserRepository::class)->save($user);

    return $user;
}

function actingAsGuardianRelationshipFeatureUser(string $userId): UserModel
{
    $model = UserModel::query()->findOrFail($userId);
    Sanctum::actingAs($model);

    return $model;
}

it('crea una relacion tutor-menor con el permiso guardian_relationships.manage', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipFeatureUser();
    $minor = persistedGuardianRelationshipFeatureUser(minor: true);
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/guardians/relationships', [
        'guardian_user_id' => $guardian->id(),
        'minor_user_id' => $minor->id(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.relationship.guardian_user_id', $guardian->id())
        ->assertJsonPath('data.relationship.minor_user_id', $minor->id())
        ->assertJsonPath('data.relationship.is_active', true);
});

it('rechaza crear una relacion tutor-menor sin el permiso guardian_relationships.manage', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipFeatureUser();
    $minor = persistedGuardianRelationshipFeatureUser(minor: true);
    actingAsRole(Role::Student);

    $this->postJson('/api/v1/guardians/relationships', [
        'guardian_user_id' => $guardian->id(),
        'minor_user_id' => $minor->id(),
    ])->assertForbidden();
});

it('rechaza vincular un tutor a un usuario que no es menor de edad', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipFeatureUser();
    $adult = persistedGuardianRelationshipFeatureUser();
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/guardians/relationships', [
        'guardian_user_id' => $guardian->id(),
        'minor_user_id' => $adult->id(),
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'GUARDIAN_RELATIONSHIP_REQUIRES_MINOR');
});

it('revoca una relacion tutor-menor con el permiso guardian_relationships.manage', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipFeatureUser();
    $minor = persistedGuardianRelationshipFeatureUser(minor: true);
    actingAsRole(Role::SuperAdmin);

    $relationshipId = $this->postJson('/api/v1/guardians/relationships', [
        'guardian_user_id' => $guardian->id(),
        'minor_user_id' => $minor->id(),
    ])->json('data.relationship.id');

    $this->deleteJson("/api/v1/guardians/relationships/{$relationshipId}")
        ->assertOk();

    actingAsGuardianRelationshipFeatureUser($guardian->id());
    $this->getJson('/api/v1/guardians/me/minors')
        ->assertOk()
        ->assertJsonCount(0, 'data.minors');
});

it('rechaza revocar una relacion tutor-menor inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->deleteJson('/api/v1/guardians/relationships/'.Str::uuid())
        ->assertStatus(404)
        ->assertJsonPath('code', 'GUARDIAN_RELATIONSHIP_NOT_FOUND');
});

it('el tutor lista los menores que tiene vinculados activamente', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipFeatureUser();
    $minor = persistedGuardianRelationshipFeatureUser(minor: true);
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/guardians/relationships', [
        'guardian_user_id' => $guardian->id(),
        'minor_user_id' => $minor->id(),
    ])->assertCreated();

    actingAsGuardianRelationshipFeatureUser($guardian->id());

    $this->getJson('/api/v1/guardians/me/minors')
        ->assertOk()
        ->assertJsonCount(1, 'data.minors')
        ->assertJsonPath('data.minors.0.user_id', $minor->id());
});

it('el tutor consulta el progreso de un menor con quien tiene una relacion activa', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipFeatureUser();
    $minor = persistedGuardianRelationshipFeatureUser(minor: true);
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/guardians/relationships', [
        'guardian_user_id' => $guardian->id(),
        'minor_user_id' => $minor->id(),
    ])->assertCreated();

    actingAsGuardianRelationshipFeatureUser($guardian->id());

    $this->getJson("/api/v1/guardians/me/minors/{$minor->id()}/progress")
        ->assertOk()
        ->assertJsonPath('data.profile.user_id', $minor->id())
        ->assertJsonPath('data.profile.is_minor', true);
});

it('rechaza consultar el progreso de un menor sin una relacion activa con el tutor', function (): void {
    /** @var TestCase $this */
    $guardian = persistedGuardianRelationshipFeatureUser();
    $minor = persistedGuardianRelationshipFeatureUser(minor: true);

    actingAsGuardianRelationshipFeatureUser($guardian->id());

    $this->getJson("/api/v1/guardians/me/minors/{$minor->id()}/progress")
        ->assertStatus(404)
        ->assertJsonPath('code', 'GUARDIAN_RELATIONSHIP_NOT_FOUND');
});

it('requiere autenticacion para todos los endpoints de tutores', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/guardians/relationships', [])->assertUnauthorized();
    $this->deleteJson('/api/v1/guardians/relationships/'.Str::uuid())->assertUnauthorized();
    $this->getJson('/api/v1/guardians/me/minors')->assertUnauthorized();
    $this->getJson('/api/v1/guardians/me/minors/'.Str::uuid().'/progress')->assertUnauthorized();
});
