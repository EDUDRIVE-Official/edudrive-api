<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedNavigationUser(): UserModel
{
    $user = User::register(id: (string) Str::uuid(), name: 'Estudiante navegación', email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash');
    app(UserRepository::class)->save($user);

    return UserModel::query()->findOrFail($user->id());
}

it('envía la página principal al acceso o al perfil según la sesión', function (): void {
    /** @var TestCase $this */
    $this->get('/')->assertRedirect(route('login'));

    $this->actingAs(persistedNavigationUser(), 'web');
    $this->get('/')->assertRedirect(route('student-profile.show'));
});

it('muestra al usuario común únicamente navegación personal', function (): void {
    /** @var TestCase $this */
    $this->actingAs(persistedNavigationUser(), 'web');

    $this->get('/mi-perfil')
        ->assertOk()
        ->assertSeeText('Mi perfil')
        ->assertSeeText('Más')
        ->assertDontSeeText('Administración')
        ->assertDontSeeText('Integraciones');
});

it('agrupa las opciones permitidas en el menú administrativo', function (): void {
    /** @var TestCase $this */
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->get('/mi-perfil')
        ->assertOk()
        ->assertSeeText('Administración')
        ->assertSeeText('Organizaciones')
        ->assertSeeText('Gobierno de IA')
        ->assertSeeText('Analítica');
});
