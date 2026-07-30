<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

function actingAsAuthenticatedUser(): UserModel
{
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    Sanctum::actingAs($model);

    return $model;
}

it('crea una organización cuando el usuario está autenticado', function (): void {
    actingAsAuthenticatedUser();

    $response = postJson('/api/v1/organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Escuela de Manejo EDUDRIVE')
        ->assertJsonPath('data.type', 'driving_school')
        ->assertJsonStructure([
            'data' => ['id', 'name', 'type'],
        ]);

    assertDatabaseHas('organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);
});

it('rechaza la creación sin autenticación', function (): void {
    postJson('/api/v1/organizations', [
        'name' => 'Sin autenticación',
        'type' => 'company',
    ])->assertUnauthorized();
});

it('rechaza datos obligatorios faltantes', function (): void {
    actingAsAuthenticatedUser();

    postJson('/api/v1/organizations', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type']);
});

it('rechaza un tipo de organización inválido', function (): void {
    actingAsAuthenticatedUser();

    postJson('/api/v1/organizations', [
        'name' => 'Organización X',
        'type' => 'not-a-real-type',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});
