<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedExperienceFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de experiencia feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('registra puntos de experiencia con el permiso experience.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $userId = persistedExperienceFeatureUserId();

    $this->postJson('/api/v1/gamification/experience/grant', [
        'user_id' => $userId,
        'points' => 50,
        'competency_id' => 'manejo-defensivo',
        'reason' => 'Completó la sesión práctica sin infracciones.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $userId)
        ->assertJsonPath('data.points', 50)
        ->assertJsonPath('data.competency_id', 'manejo-defensivo');
});

it('rechaza registrar experiencia sin el permiso experience.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $userId = persistedExperienceFeatureUserId();

    $this->postJson('/api/v1/gamification/experience/grant', [
        'user_id' => $userId,
        'points' => 50,
        'reason' => 'Motivo',
    ])->assertForbidden();
});

it('rechaza registrar puntos de experiencia no positivos', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $userId = persistedExperienceFeatureUserId();

    $this->postJson('/api/v1/gamification/experience/grant', [
        'user_id' => $userId,
        'points' => 0,
        'reason' => 'Motivo',
    ])->assertStatus(422);
});

it('el usuario consulta su propio resumen de experiencia con nivel general y por competencia', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);

    actingAsRole(Role::SuperAdmin);
    $this->postJson('/api/v1/gamification/experience/grant', ['user_id' => $userId, 'points' => 70, 'competency_id' => 'manejo-defensivo', 'reason' => 'Motivo 1'])
        ->assertCreated();
    $this->postJson('/api/v1/gamification/experience/grant', ['user_id' => $userId, 'points' => 60, 'competency_id' => 'manejo-defensivo', 'reason' => 'Motivo 2'])
        ->assertCreated();

    actingAsUserId($userId);
    $this->getJson('/api/v1/gamification/experience/me')
        ->assertOk()
        ->assertJsonPath('data.total_points', 130)
        ->assertJsonPath('data.general_level', 2)
        ->assertJsonPath('data.competencies.0.competency_id', 'manejo-defensivo')
        ->assertJsonPath('data.competencies.0.total_points', 130)
        ->assertJsonPath('data.competencies.0.level', 2);
});

it('devuelve nivel uno cuando el usuario no tiene experiencia registrada', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);

    $this->getJson('/api/v1/gamification/experience/me')
        ->assertOk()
        ->assertJsonPath('data.total_points', 0)
        ->assertJsonPath('data.general_level', 1)
        ->assertJsonPath('data.competencies', []);
});

it('requiere autenticacion para los endpoints de experiencia', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/gamification/experience/me')->assertUnauthorized();
    $this->postJson('/api/v1/gamification/experience/grant', [])->assertUnauthorized();
});
