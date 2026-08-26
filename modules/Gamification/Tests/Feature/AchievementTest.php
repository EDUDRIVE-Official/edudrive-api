<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedAchievementFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de logros feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedAchievementFeature(?string $code = null): Achievement
{
    $achievement = Achievement::create(
        id: AchievementId::fromString((string) Str::uuid()),
        code: AchievementCode::fromString($code ?? 'LOGRO-'.strtoupper((string) Str::random(6))),
        name: 'Primer curso completado',
        description: 'Se otorga al completar el primer curso.',
        earningRule: 'Completar cualquier curso por primera vez.',
    );
    app(AchievementRepository::class)->save($achievement);

    return $achievement;
}

it('crea un logro con el permiso achievements.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/gamification/achievements', [
        'code' => 'primer-curso-completado',
        'name' => 'Primer curso completado',
        'description' => 'Se otorga al completar el primer curso.',
        'earning_rule' => 'Completar cualquier curso por primera vez.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'PRIMER-CURSO-COMPLETADO')
        ->assertJsonPath('data.status', 'active');
});

it('rechaza crear un logro sin el permiso achievements.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/gamification/achievements', [
        'code' => 'primer-curso-completado',
        'name' => 'Primer curso completado',
        'description' => 'Se otorga al completar el primer curso.',
        'earning_rule' => 'Completar cualquier curso por primera vez.',
    ])->assertForbidden();
});

it('rechaza crear un segundo logro con el mismo codigo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $achievement = persistedAchievementFeature();

    $this->postJson('/api/v1/gamification/achievements', [
        'code' => $achievement->code()->value(),
        'name' => 'Otro nombre',
        'description' => 'Otra descripcion',
        'earning_rule' => 'Otra regla',
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ACHIEVEMENT_ALREADY_EXISTS');
});

it('lista el catalogo de logros incluso para un estudiante', function (): void {
    /** @var TestCase $this */
    $achievement = persistedAchievementFeature();
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/gamification/achievements')
        ->assertOk()
        ->assertJsonPath('data.0.id', $achievement->id()->value());
});

it('consulta un logro por id', function (): void {
    /** @var TestCase $this */
    $achievement = persistedAchievementFeature();
    actingAsRole(Role::Student);

    $this->getJson("/api/v1/gamification/achievements/{$achievement->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $achievement->id()->value());
});

it('retira un logro con el permiso achievements.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $achievement = persistedAchievementFeature();

    $this->postJson("/api/v1/gamification/achievements/{$achievement->id()->value()}/retire", ['reason' => 'Ya no aplica'])
        ->assertOk()
        ->assertJsonPath('data.status', 'retired');
});

it('rechaza retirar un logro sin el permiso achievements.manage', function (): void {
    /** @var TestCase $this */
    $achievement = persistedAchievementFeature();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/gamification/achievements/{$achievement->id()->value()}/retire")
        ->assertForbidden();
});

it('otorga un logro a un usuario con el permiso achievements.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $achievement = persistedAchievementFeature();
    $userId = persistedAchievementFeatureUserId();

    $this->postJson("/api/v1/gamification/achievements/{$achievement->id()->value()}/grant", [
        'user_id' => $userId,
        'evidence' => 'Completó el curso con 95% de aciertos.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.achievement_id', $achievement->id()->value())
        ->assertJsonPath('data.user_id', $userId);
});

it('rechaza otorgar el mismo logro dos veces al mismo usuario', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $achievement = persistedAchievementFeature();
    $userId = persistedAchievementFeatureUserId();

    $this->postJson("/api/v1/gamification/achievements/{$achievement->id()->value()}/grant", ['user_id' => $userId, 'evidence' => 'Primera vez'])
        ->assertCreated();

    $this->postJson("/api/v1/gamification/achievements/{$achievement->id()->value()}/grant", ['user_id' => $userId, 'evidence' => 'Segunda vez'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ACHIEVEMENT_ALREADY_GRANTED');
});

it('el usuario consulta sus propios logros obtenidos', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $achievement = persistedAchievementFeature();

    actingAsRole(Role::SuperAdmin);
    $this->postJson("/api/v1/gamification/achievements/{$achievement->id()->value()}/grant", ['user_id' => $userId, 'evidence' => 'Evidencia'])
        ->assertCreated();

    actingAsUserId($userId);
    $this->getJson('/api/v1/gamification/achievements/me')
        ->assertOk()
        ->assertJsonPath('data.0.achievement_id', $achievement->id()->value());
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $achievement = persistedAchievementFeature();

    $this->getJson('/api/v1/gamification/achievements/me')->assertUnauthorized();
    $this->getJson('/api/v1/gamification/achievements')->assertUnauthorized();
    $this->getJson("/api/v1/gamification/achievements/{$achievement->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/gamification/achievements', [])->assertUnauthorized();
});
