<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de almacenamiento de archivos está disponible', function (): void {
    getJson('/api/v1/files/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'FileStorage',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
