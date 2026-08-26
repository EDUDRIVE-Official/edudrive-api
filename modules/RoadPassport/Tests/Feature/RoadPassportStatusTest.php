<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de pasaporte vial está disponible', function (): void {
    getJson('/api/v1/road-passport/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'RoadPassport',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
