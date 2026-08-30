<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;
use Tests\TestCase;

function registerStudentProfileTestUser(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de perfil',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
        dateOfBirth: new DateTimeImmutable('2010-01-01'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

it('consulta el propio perfil compuesto sin haberlo completado todavia', function (): void {
    /** @var TestCase $this */
    $user = registerStudentProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/profile')
        ->assertOk()
        ->assertJsonPath('data.profile.name', $user->name())
        ->assertJsonPath('data.profile.is_minor', true)
        ->assertJsonPath('data.profile.education_level', null)
        ->assertJsonPath('data.profile.road_passport', null)
        ->assertJsonPath('data.profile.enrollments', []);
});

it('actualiza el propio perfil y lo refleja al consultarlo de nuevo', function (): void {
    /** @var TestCase $this */
    $user = registerStudentProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/auth/me/profile', [
            'education_level' => 'Universitario incompleto',
            'accessibility_needs' => 'Requiere más tiempo en exámenes.',
            'learning_preferences' => 'Prefiere contenido en video.',
        ])
        ->assertOk()
        ->assertJsonPath('data.profile.education_level', 'Universitario incompleto');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/profile')
        ->assertOk()
        ->assertJsonPath('data.profile.education_level', 'Universitario incompleto')
        ->assertJsonPath('data.profile.accessibility_needs', 'Requiere más tiempo en exámenes.')
        ->assertJsonPath('data.profile.learning_preferences', 'Prefiere contenido en video.');
});

it('incluye el estado del pasaporte vial cuando el usuario ya tiene uno emitido', function (): void {
    /** @var TestCase $this */
    $user = registerStudentProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    app(RoadPassportRepository::class)->save(RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: $user->id(),
    ));

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/profile')
        ->assertOk()
        ->assertJsonPath('data.profile.road_passport.status', 'active');
});

it('rechaza actualizar el perfil con un texto demasiado largo', function (): void {
    /** @var TestCase $this */
    $user = registerStudentProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/auth/me/profile', [
            'education_level' => str_repeat('a', 256),
        ])
        ->assertJsonValidationErrors('education_level');
});

it('requiere autenticacion para consultar o actualizar el perfil', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/auth/me/profile')->assertUnauthorized();
    $this->putJson('/api/v1/auth/me/profile', [])->assertUnauthorized();
});
