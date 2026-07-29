<?php

declare(strict_types=1);

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

it('crea un curso académico', function (): void {
    $response = postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'edu-010',
            'title' => 'Introducción a la seguridad vial',
            'description' => 'Curso base de EDUDRIVE.',
        ],
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.code', 'EDU-010')
        ->assertJsonPath(
            'data.title',
            'Introducción a la seguridad vial',
        )
        ->assertJsonPath(
            'data.description',
            'Curso base de EDUDRIVE.',
        )
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'title',
                'description',
                'status',
            ],
        ]);

    assertDatabaseHas('academic_courses', [
        'code' => 'EDU-010',
        'title' => 'Introducción a la seguridad vial',
        'description' => 'Curso base de EDUDRIVE.',
        'status' => 'draft',
    ]);
});

it('rechaza la creación de un curso sin datos obligatorios', function (): void {
    postJson('/api/v1/academic/courses', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
            'title',
        ]);
});

it('rechaza un código con formato inválido', function (): void {
    postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'EDU_010',
            'title' => 'Curso inválido',
        ],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
        ]);
});
