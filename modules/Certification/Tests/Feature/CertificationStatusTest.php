<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de certificación está disponible', function (): void {
    getJson('/api/v1/certification/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Certification',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
