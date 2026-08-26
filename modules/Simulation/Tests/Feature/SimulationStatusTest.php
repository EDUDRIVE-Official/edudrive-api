<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de simulación está disponible', function (): void {
    getJson('/api/v1/simulation/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Simulation',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
