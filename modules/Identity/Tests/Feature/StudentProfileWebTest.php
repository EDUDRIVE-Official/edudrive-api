<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

function persistedStudentProfileWebTestUser(): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('2000-01-01'),
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('redirige a un invitado que intenta ver su perfil', function (): void {
    /** @var TestCase $this */
    $this->get('/mi-perfil')->assertRedirect(route('login'));
});

it('muestra el perfil del estudiante autenticado con datos vacios la primera vez', function (): void {
    /** @var TestCase $this */
    $user = persistedStudentProfileWebTestUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->get('/mi-perfil');

    $response->assertOk();
    $response->assertSeeText('Estudiante Web');
});

it('muestra el pasaporte vial y las matriculas cuando existen', function (): void {
    /** @var TestCase $this */
    $user = persistedStudentProfileWebTestUser();

    app(StudentProfileRepository::class)->save(StudentProfile::restore(
        userId: $user->id(),
        educationLevel: 'Secundaria',
        accessibilityNeeds: null,
        learningPreferences: 'Video',
        updatedAt: new DateTimeImmutable,
    ));

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->get('/mi-perfil');

    $response->assertOk();
    $response->assertSee('value="Secundaria"', false);
    $response->assertSee('Video', false);
});

it('actualiza el perfil del estudiante y redirige con un mensaje de exito', function (): void {
    /** @var TestCase $this */
    $user = persistedStudentProfileWebTestUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->put('/mi-perfil', [
        'education_level' => 'Universitaria',
        'accessibility_needs' => 'Ninguna',
        'learning_preferences' => 'Lectura',
    ]);

    $response->assertRedirect(route('student-profile.show'));
    $response->assertSessionHas('status');

    $profile = app(StudentProfileRepository::class)->findByUserId($user->id());
    expect($profile?->educationLevel())->toBe('Universitaria')
        ->and($profile?->learningPreferences())->toBe('Lectura');
});

it('vuelve al perfil con errores cuando un campo excede la longitud permitida', function (): void {
    /** @var TestCase $this */
    $user = persistedStudentProfileWebTestUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->put('/mi-perfil', [
        'education_level' => str_repeat('a', 300),
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['education_level']);
});
