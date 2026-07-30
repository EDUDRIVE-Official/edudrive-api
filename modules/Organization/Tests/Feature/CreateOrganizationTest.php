<?php

declare(strict_types=1);

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

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
