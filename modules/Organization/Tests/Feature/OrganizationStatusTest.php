<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de organizaciones está disponible', function (): void {
    getJson('/api/v1/organizations/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Organization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
