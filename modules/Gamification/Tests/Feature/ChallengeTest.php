<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedChallengeFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de retos feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedChallengeFeature(
    ?string $code = null,
    ?DateTimeImmutable $startsAt = null,
    ?DateTimeImmutable $endsAt = null,
): Challenge {
    $challenge = Challenge::create(
        id: ChallengeId::fromString((string) Str::uuid()),
        code: ChallengeCode::fromString($code ?? 'RETO-'.strtoupper((string) Str::random(6))),
        name: 'Semana de manejo seguro',
        description: 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        type: ChallengeType::Individual,
        reward: '100 puntos de experiencia.',
        startsAt: $startsAt ?? new DateTimeImmutable('-1 day'),
        endsAt: $endsAt ?? new DateTimeImmutable('+7 days'),
    );
    app(ChallengeRepository::class)->save($challenge);

    return $challenge;
}

it('crea un reto con el permiso challenges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/gamification/challenges', [
        'code' => 'semana-manejo-seguro',
        'name' => 'Semana de manejo seguro',
        'description' => 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        'type' => 'individual',
        'reward' => '100 puntos de experiencia.',
        'starts_at' => (new DateTimeImmutable('+1 day'))->format(DATE_ATOM),
        'ends_at' => (new DateTimeImmutable('+8 days'))->format(DATE_ATOM),
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'SEMANA-MANEJO-SEGURO')
        ->assertJsonPath('data.status', 'active');
});

it('rechaza crear un reto sin el permiso challenges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->postJson('/api/v1/gamification/challenges', [
        'code' => 'semana-manejo-seguro',
        'name' => 'Semana de manejo seguro',
        'description' => 'Descripcion',
        'type' => 'individual',
        'reward' => 'Recompensa',
        'starts_at' => (new DateTimeImmutable('+1 day'))->format(DATE_ATOM),
        'ends_at' => (new DateTimeImmutable('+8 days'))->format(DATE_ATOM),
    ])->assertForbidden();
});

it('rechaza crear un segundo reto con el mismo codigo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $challenge = persistedChallengeFeature();

    $this->postJson('/api/v1/gamification/challenges', [
        'code' => $challenge->code()->value(),
        'name' => 'Otro nombre',
        'description' => 'Otra descripcion',
        'type' => 'group',
        'reward' => 'Otra recompensa',
        'starts_at' => (new DateTimeImmutable('+1 day'))->format(DATE_ATOM),
        'ends_at' => (new DateTimeImmutable('+8 days'))->format(DATE_ATOM),
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'CHALLENGE_ALREADY_EXISTS');
});

it('lista el catalogo de retos incluso para un estudiante', function (): void {
    /** @var TestCase $this */
    $challenge = persistedChallengeFeature();
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/gamification/challenges')
        ->assertOk()
        ->assertJsonPath('data.0.id', $challenge->id()->value());
});

it('consulta un reto por id', function (): void {
    /** @var TestCase $this */
    $challenge = persistedChallengeFeature();
    actingAsRole(Role::Student);

    $this->getJson("/api/v1/gamification/challenges/{$challenge->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $challenge->id()->value());
});

it('retira un reto con el permiso challenges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $challenge = persistedChallengeFeature();

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/retire", ['reason' => 'Ya no aplica'])
        ->assertOk()
        ->assertJsonPath('data.status', 'retired');
});

it('rechaza retirar un reto sin el permiso challenges.manage', function (): void {
    /** @var TestCase $this */
    $challenge = persistedChallengeFeature();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/retire")
        ->assertForbidden();
});

it('une a un usuario a un reto activo dentro de su ventana con el permiso challenges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $challenge = persistedChallengeFeature();
    $userId = persistedChallengeFeatureUserId();

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/join", ['user_id' => $userId])
        ->assertCreated()
        ->assertJsonPath('data.challenge_id', $challenge->id()->value())
        ->assertJsonPath('data.user_id', $userId)
        ->assertJsonPath('data.status', 'joined');
});

it('rechaza unir dos veces al mismo usuario al mismo reto', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $challenge = persistedChallengeFeature();
    $userId = persistedChallengeFeatureUserId();

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/join", ['user_id' => $userId])
        ->assertCreated();

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/join", ['user_id' => $userId])
        ->assertStatus(409)
        ->assertJsonPath('code', 'CHALLENGE_ALREADY_JOINED');
});

it('rechaza unir a un usuario fuera de la ventana de vigencia del reto', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $challenge = persistedChallengeFeature(startsAt: new DateTimeImmutable('+1 day'), endsAt: new DateTimeImmutable('+8 days'));
    $userId = persistedChallengeFeatureUserId();

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/join", ['user_id' => $userId])
        ->assertStatus(422)
        ->assertJsonPath('code', 'CHALLENGE_NOT_AVAILABLE');
});

it('completa la participacion de un usuario con el permiso challenges.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $challenge = persistedChallengeFeature();
    $userId = persistedChallengeFeatureUserId();
    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/join", ['user_id' => $userId])
        ->assertCreated();

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/complete", [
        'user_id' => $userId,
        'evidence' => 'Completó las cinco sesiones sin infracciones.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.evidence', 'Completó las cinco sesiones sin infracciones.');
});

it('rechaza completar una participacion inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $challenge = persistedChallengeFeature();
    $userId = persistedChallengeFeatureUserId();

    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/complete", ['user_id' => $userId])
        ->assertStatus(404)
        ->assertJsonPath('code', 'CHALLENGE_PARTICIPATION_NOT_FOUND');
});

it('el usuario consulta sus propias participaciones', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $challenge = persistedChallengeFeature();

    actingAsRole(Role::SuperAdmin);
    $this->postJson("/api/v1/gamification/challenges/{$challenge->id()->value()}/join", ['user_id' => $userId])
        ->assertCreated();

    actingAsUserId($userId);
    $this->getJson('/api/v1/gamification/challenges/me')
        ->assertOk()
        ->assertJsonPath('data.0.challenge_id', $challenge->id()->value());
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $challenge = persistedChallengeFeature();

    $this->getJson('/api/v1/gamification/challenges/me')->assertUnauthorized();
    $this->getJson('/api/v1/gamification/challenges')->assertUnauthorized();
    $this->getJson("/api/v1/gamification/challenges/{$challenge->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/gamification/challenges', [])->assertUnauthorized();
});
