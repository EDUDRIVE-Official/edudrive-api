<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedBadgeFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de insignias feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedBadgeFeature(?string $code = null): Badge
{
    $badge = Badge::create(
        id: BadgeId::fromString((string) Str::uuid()),
        code: BadgeCode::fromString($code ?? 'INSIGNIA-'.strtoupper((string) Str::random(6))),
        name: 'Conductor defensivo',
        description: 'Se otorga por demostrar manejo defensivo consistente.',
        criteria: 'Completar 10 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Bronze,
    );
    app(BadgeRepository::class)->save($badge);

    return $badge;
}

it('crea una insignia con el permiso badges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/gamification/badges', [
        'code' => 'conductor-defensivo',
        'name' => 'Conductor defensivo',
        'description' => 'Se otorga por demostrar manejo defensivo consistente.',
        'criteria' => 'Completar 10 sesiones prácticas sin infracciones.',
        'category' => 'practical',
        'level' => 'bronze',
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'CONDUCTOR-DEFENSIVO')
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.status', 'active');
});

it('rechaza crear una insignia sin el permiso badges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/gamification/badges', [
        'code' => 'conductor-defensivo',
        'name' => 'Conductor defensivo',
        'description' => 'Se otorga por demostrar manejo defensivo consistente.',
        'criteria' => 'Completar 10 sesiones prácticas sin infracciones.',
        'category' => 'practical',
        'level' => 'bronze',
    ])->assertForbidden();
});

it('rechaza crear una segunda insignia con el mismo codigo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $badge = persistedBadgeFeature();

    $this->postJson('/api/v1/gamification/badges', [
        'code' => $badge->code()->value(),
        'name' => 'Otro nombre',
        'description' => 'Otra descripcion',
        'criteria' => 'Otro criterio',
        'category' => 'educational',
        'level' => 'silver',
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'BADGE_ALREADY_EXISTS');
});

it('lista el catalogo de insignias incluso para un estudiante', function (): void {
    /** @var TestCase $this */
    $badge = persistedBadgeFeature();
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/gamification/badges')
        ->assertOk()
        ->assertJsonPath('data.0.id', $badge->id()->value());
});

it('consulta una insignia por id', function (): void {
    /** @var TestCase $this */
    $badge = persistedBadgeFeature();
    actingAsRole(Role::Student);

    $this->getJson("/api/v1/gamification/badges/{$badge->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $badge->id()->value());
});

it('actualiza el contenido de una insignia e incrementa su version con el permiso badges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $badge = persistedBadgeFeature();

    $this->putJson("/api/v1/gamification/badges/{$badge->id()->value()}", [
        'name' => 'Conductor defensivo avanzado',
        'description' => 'Descripcion actualizada.',
        'criteria' => 'Completar 20 sesiones prácticas sin infracciones.',
        'category' => 'practical',
        'level' => 'silver',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Conductor defensivo avanzado')
        ->assertJsonPath('data.level', 'silver')
        ->assertJsonPath('data.version', 2);
});

it('rechaza actualizar una insignia sin el permiso badges.manage', function (): void {
    /** @var TestCase $this */
    $badge = persistedBadgeFeature();
    actingAsRole(Role::Teacher);

    $this->putJson("/api/v1/gamification/badges/{$badge->id()->value()}", [
        'name' => 'Nombre',
        'description' => 'Descripcion',
        'criteria' => 'Criterio',
        'category' => 'practical',
        'level' => 'gold',
    ])->assertForbidden();
});

it('retira una insignia con el permiso badges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $badge = persistedBadgeFeature();

    $this->postJson("/api/v1/gamification/badges/{$badge->id()->value()}/retire", ['reason' => 'Ya no aplica'])
        ->assertOk()
        ->assertJsonPath('data.status', 'retired');
});

it('rechaza retirar una insignia sin el permiso badges.manage', function (): void {
    /** @var TestCase $this */
    $badge = persistedBadgeFeature();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/gamification/badges/{$badge->id()->value()}/retire")
        ->assertForbidden();
});

it('otorga una insignia a un usuario con el permiso badges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $badge = persistedBadgeFeature();
    $userId = persistedBadgeFeatureUserId();

    $this->postJson("/api/v1/gamification/badges/{$badge->id()->value()}/grant", [
        'user_id' => $userId,
        'evidence' => 'Completó 10 sesiones prácticas sin infracciones.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.badge_id', $badge->id()->value())
        ->assertJsonPath('data.user_id', $userId)
        ->assertJsonPath('data.awarded_version', 1);
});

it('rechaza otorgar la misma insignia dos veces al mismo usuario', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $badge = persistedBadgeFeature();
    $userId = persistedBadgeFeatureUserId();

    $this->postJson("/api/v1/gamification/badges/{$badge->id()->value()}/grant", ['user_id' => $userId, 'evidence' => 'Primera vez'])
        ->assertCreated();

    $this->postJson("/api/v1/gamification/badges/{$badge->id()->value()}/grant", ['user_id' => $userId, 'evidence' => 'Segunda vez'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'BADGE_ALREADY_GRANTED');
});

it('el usuario consulta sus propias insignias obtenidas', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $badge = persistedBadgeFeature();

    actingAsRole(Role::SuperAdmin);
    $this->postJson("/api/v1/gamification/badges/{$badge->id()->value()}/grant", ['user_id' => $userId, 'evidence' => 'Evidencia'])
        ->assertCreated();

    actingAsUserId($userId);
    $this->getJson('/api/v1/gamification/badges/me')
        ->assertOk()
        ->assertJsonPath('data.0.badge_id', $badge->id()->value());
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $badge = persistedBadgeFeature();

    $this->getJson('/api/v1/gamification/badges/me')->assertUnauthorized();
    $this->getJson('/api/v1/gamification/badges')->assertUnauthorized();
    $this->getJson("/api/v1/gamification/badges/{$badge->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/gamification/badges', [])->assertUnauthorized();
});
