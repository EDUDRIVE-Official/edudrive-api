<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de autorización está disponible', function (): void {
    getJson('/api/v1/authorization/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Authorization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
