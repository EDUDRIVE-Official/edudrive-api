<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de administración está disponible', function (): void {
    getJson('/api/v1/admin/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Admin',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
