<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedRoadPassportFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Titular de pasaporte feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedRoadPassportFeature(?string $userId = null): RoadPassport
{
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: $userId ?? persistedRoadPassportFeatureUserId(),
        issuedAt: new DateTimeImmutable('now'),
    );
    app(RoadPassportRepository::class)->save($passport);

    return $passport;
}

it('emite un pasaporte vial con el permiso road_passports.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $userId = persistedRoadPassportFeatureUserId();

    $this->postJson('/api/v1/road-passport', ['user_id' => $userId])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $userId)
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.level', 1);
});

it('rechaza emitir un pasaporte sin el permiso road_passports.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/road-passport', ['user_id' => (string) Str::uuid()])
        ->assertForbidden();
});

it('rechaza emitir un segundo pasaporte para el mismo usuario', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $passport = persistedRoadPassportFeature();

    $this->postJson('/api/v1/road-passport', ['user_id' => $passport->userId()])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ROAD_PASSPORT_ALREADY_EXISTS');
});

it('consulta el propio pasaporte vial', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $passport = persistedRoadPassportFeature($userId);

    $this->getJson('/api/v1/road-passport/me')
        ->assertOk()
        ->assertJsonPath('data.id', $passport->id()->value());
});

it('responde 404 al consultar el propio pasaporte si no tiene uno emitido', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());

    $this->getJson('/api/v1/road-passport/me')
        ->assertNotFound()
        ->assertJsonPath('code', 'ROAD_PASSPORT_NOT_FOUND');
});

it('consulta un pasaporte vial propio por id', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $passport = persistedRoadPassportFeature($userId);

    $this->getJson("/api/v1/road-passport/{$passport->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $passport->id()->value());
});

it('rechaza consultar un pasaporte ajeno sin road_passports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $passport = persistedRoadPassportFeature();

    $this->getJson("/api/v1/road-passport/{$passport->id()->value()}")
        ->assertNotFound()
        ->assertJsonPath('code', 'ROAD_PASSPORT_NOT_FOUND');
});

it('permite consultar un pasaporte ajeno con road_passports.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $passport = persistedRoadPassportFeature();

    $this->getJson("/api/v1/road-passport/{$passport->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $passport->id()->value());
});

it('suspende, reactiva, cambia de nivel y revoca un pasaporte con road_passports.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $passport = persistedRoadPassportFeature();
    $id = $passport->id()->value();

    $this->postJson("/api/v1/road-passport/{$id}/suspend", ['reason' => 'Documentación pendiente'])
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended');

    $this->postJson("/api/v1/road-passport/{$id}/reactivate")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->putJson("/api/v1/road-passport/{$id}/level", ['level' => 5])
        ->assertOk()
        ->assertJsonPath('data.level', 5);

    $this->postJson("/api/v1/road-passport/{$id}/revoke", ['reason' => 'Fraude'])
        ->assertOk()
        ->assertJsonPath('data.status', 'revoked');
});

it('rechaza mutar un pasaporte sin road_passports.manage', function (): void {
    /** @var TestCase $this */
    $passport = persistedRoadPassportFeature();
    actingAsRole(Role::Teacher);
    $id = $passport->id()->value();

    $this->postJson("/api/v1/road-passport/{$id}/suspend")->assertForbidden();
    $this->postJson("/api/v1/road-passport/{$id}/reactivate")->assertForbidden();
    $this->postJson("/api/v1/road-passport/{$id}/revoke")->assertForbidden();
    $this->putJson("/api/v1/road-passport/{$id}/level", ['level' => 2])->assertForbidden();
});

it('responde 422 ante una transicion invalida', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $passport = persistedRoadPassportFeature();

    $this->postJson("/api/v1/road-passport/{$passport->id()->value()}/reactivate")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_ROAD_PASSPORT_TRANSITION');
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $passport = persistedRoadPassportFeature();

    $this->getJson('/api/v1/road-passport/me')->assertUnauthorized();
    $this->getJson("/api/v1/road-passport/{$passport->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/road-passport', ['user_id' => (string) Str::uuid()])->assertUnauthorized();
});
